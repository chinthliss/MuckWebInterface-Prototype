<?php

namespace App;

use App\Helpers\MuckInterop;
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

    //region Retrieval commands

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

    public function retrieveById($identifier)
    {
        $accountQuery = $this->getRetrievalQuery()
            ->where('accounts.aid', $identifier)
            ->first();
        if ($accountQuery) return User::fromQuery($accountQuery);
        else return null;
    }

    public function retrieveByToken($identifier, $token)
    {
        $accountQuery = $this->getRetrievalQuery()
            ->where('accounts.aid', $identifier)
            ->first();
        if (!$accountQuery) return null;
        $rememberToken = $accountQuery->remember_token;
        if ($rememberToken && hash_equals($rememberToken, $token)) {
            return User::fromQuery($accountQuery);
        } else return null;
    }

    public function retrieveByCredentials(array $credentials)
    {
        $accountQuery = null;
        if (array_key_exists('email', $credentials)) {
            $accountQuery = $this->getRetrievalQuery()
                ->where('accounts.email', $credentials['email'])
                ->first();
        }
        //TODO: Retrieve #dbref from muck if credentials email matches character name
        if ($accountQuery) return User::fromQuery($accountQuery);
        else return null;
    }

    //endregion

    public function updateRememberToken(Authenticatable $user, $token)
    {
        DB::table('accounts')
            ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
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
        //TODO: If we have an associated character, check password against muck
        if (method_exists($user, 'getPasswordType')) {
            if ($user->getPasswordType() == 'SHA1SALT') {
                return MuckInterop::verifySHA1SALTPassword($credentials['password'], $user->getAuthPassword());
            }
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
        $user = User::fromQuery($accountQuery);
        DB::table('account_emails')->insert([
            'email' => $email,
            'aid' => $user->getAid(),
            'created_at' => Carbon::now()
        ]);
        return $user;
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
        $query = DB::table('account_emails')->where([
            'email' => $email
        ])->first();
        if (!$query) {
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

}
