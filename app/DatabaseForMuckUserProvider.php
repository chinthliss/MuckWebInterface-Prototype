<?php

namespace App;

use App\Contracts\MuckConnection;
use App\Helpers\MuckInterop;
use App\Muck\MuckCharacter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Class DatabaseForMuckUserProvider
 *
 */
class DatabaseForMuckUserProvider implements UserProvider
{

    private $muckConnection = null;

    public function __construct(MuckConnection $muckConnection)
    {
        $this->muckConnection = $muckConnection;
    }

    //region Retrieval

    /**
     * Gets a base query that contains the required columns for creating a User object.
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getRetrievalQuery()
    {
        return DB::table('accounts')
            ->select('accounts.*', 'account_emails.verified_at')
            ->leftJoin('account_emails', 'account_emails.email', '=', 'accounts.email');
    }

    //Used when user is logged in, called with accountId (aid)
    public function retrieveById($identifier)
    {
        debug("RetrieveById:", $identifier);
        //Retrieve account details from database first
        $accountQuery = $this->getRetrievalQuery()
            ->where('accounts.aid', $identifier)
            ->first();
        if (!$accountQuery) return null;
        $user = User::fromDatabaseResponse($accountQuery);
        //Now ask the muck for the characters on this account
        $characters = $this->muckConnection->getCharactersOf($identifier);
        $user->characters = $characters;
        //See if a character is saved by the session - this may be overridden later by the present page
        $characterDbref = session('lastCharacterDbref');
        if ($characterDbref && $user->characters->has($characterDbref)) {
            $user->setCharacter($user->characters[$characterDbref]);
        }
        debug($user);
        return $user;
    }

    public function retrieveByToken($identifier, $token)
    {
        debug("RetrieveByToken:", $identifier, $token);
        $accountQuery = $this->getRetrievalQuery()
            ->where('accounts.aid', $identifier)
            ->first();
        if (!$accountQuery) return null;
        $rememberToken = $accountQuery->remember_token;
        if ($rememberToken && hash_equals($rememberToken, $token)) {
            return User::fromDatabaseResponse($accountQuery);
        } else return null;
    }

    public function retrieveByCredentials(array $credentials)
    {
        debug("RetrieveByCredentials", $credentials);
        if (!array_key_exists('email', $credentials)) return null;
        if (strpos($credentials['email'], '@')) {
            //Looks like an email, so try database
            $accountQuery = $this->getRetrievalQuery()
                ->where('accounts.email', $credentials['email'])
                ->first();
            if ($accountQuery) return User::fromDatabaseResponse($accountQuery);
        } else {
            //Try from muck
            $lookup = $this->muckConnection->retrieveByCredentials($credentials);
            if ($lookup) {
                list($aid, $character) = $lookup;
                $accountQuery = $this->getRetrievalQuery()
                    ->where('accounts.aid', $aid)
                    ->first();
                if (!$accountQuery) return null; //Account referenced by muck but wasn't found in DB!
                $user = User::fromDatabaseResponse($accountQuery);
                session(['lastCharacterDbref'=>$character->getDbref()]);
                $user->setCharacter($character);
                return $user;
            }
        }
        return null;
    }

    //endregion Retrieval

    public function updateRememberToken(Authenticatable $user, $token)
    {
        DB::table('accounts')
            ->where('aid', $user->getAid())
            ->update([$user->getRememberTokenName() => $token]);
    }


    /**
     * Validate a user against the given credentials.
     *
     * @param Authenticatable $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // return Hash::check($credentials['password'], $user->getAuthPassword());
        debug("validateCredentials:", $user, $credentials);
        //Try the database retrieved details first
        if (method_exists($user, 'getPasswordType')
            && $user->getPasswordType() == 'SHA1SALT'
            && MuckInterop::verifySHA1SALTPassword($credentials['password'], $user->getAuthPassword()))
            return true;
        //Otherwise try the muck
        if (method_exists($user, 'getCharacter') && $user->getCharacter()) {
            return $this->muckConnection->validateCredentials($user->getCharacter(), $credentials);
        }
        return false;
    }

    /**
     * Creates an account and returns a User object to represent it
     * @param string $email
     * @param string $password
     * @return User
     * @throws \Exception
     */
    public function createAccount(string $email, string $password)
    {
        // Need to insert into DB first in order to get the id assigned
        DB::table('accounts')->insert([
            'email' => $email,
            'uuid' => Str::uuid(),
            'password' => MuckInterop::createSHA1SALTPassword($password),
            'password_type' => 'SHA1SALT',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $accountQuery = DB::table('accounts')->where('email', $email)->first();
        $user = User::fromDatabaseResponse($accountQuery);
        DB::table('account_emails')->insert([
            'email' => $email,
            'aid' => $user->getAid(),
            'created_at' => Carbon::now()
        ]);
        return $user;
    }

    public function updateLastUpdated(User $user)
    {
        DB::table('accounts')->where([
            'aid' => $user->getAid()
        ])->update([
            'updated_at' => Carbon::now()
        ]);
    }

    public function updatePassword(User $user, string $password, $password_type)
    {
        DB::table('accounts')->where([
            'aid' => $user->getAid()
        ])->update([
            'password' => $password,
            'password_type' => $password_type,
            'updated_at' => Carbon::now()
        ]);
    }

    //region Email

    /**
     * @param User $user
     * @param string $email
     * @return bool Whether new email is verified
     */
    public function updateEmail(User $user, string $email)
    {
        //Because historic code may not have made an entry for existing mail, check on such
        if ($existingEmail = $user->getEmailForVerification()) {
            $query = DB::table('account_emails')->where([
                'email' => $existingEmail
            ])->first();
            if (!$query) {
                DB::table('account_emails')->insert([
                    'email' => $existingEmail,
                    'aid' => $user->getAid(),
                    'created_at' => Carbon::now()
                ]);
            }
        }
        //Need to make sure there's a reference in account_emails
        $newEmailQuery = DB::table('account_emails')->where([
            'email' => $email
        ])->first();
        if (!$newEmailQuery) {
            DB::table('account_emails')->insert([
                'email' => $email,
                'aid' => $user->getAid(),
                'created_at' => Carbon::now()
            ]);
        }
        DB::table('accounts')->where([
            'aid' => $user->getAid()
        ])->update([
            'email' => $email,
            'updated_at' => Carbon::now()
        ]);
        return ($newEmailQuery && $newEmailQuery->verified_at);
    }

    public function isEmailAvailable(string $email)
    {
        $aid = DB::table('accounts')->where([
            'email' => $email
        ])->value('aid');
        if ($aid) return false;
        $aid = DB::table('account_emails')->where([
            'email' => $email
        ])->value('aid');
        return $aid ? false : true;
    }

    public function markEmailAsVerified(User $user, string $email)
    {
        // Verify email - this may not exist if it was created from outside
        DB::table('account_emails')->updateOrInsert(
            ['aid' => $user->getAid(), 'email' => $email],
            ['verified_at' => Carbon::now()]
        );
        // And make it active email
        DB::table('accounts')->where([
            'aid' => $user->getAid()
        ])->update([
            'email' => $email,
            'updated_at' => Carbon::now()
        ]);
    }

    /**
     * Get all emails to do with a user in the form
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    public function getEmails(User $user)
    {
        return DB::table('account_emails')->select([
            'email', 'created_at', 'verified_at'
        ])->where([
            'aid' => $user->getAid()
        ])->get();
    }
    // endregion Email

}
