<?php

namespace App;

use App\Muck\MuckConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Error;
use MuckInterop;

/**
 * Class DatabaseForMuckUserProvider
 *
 */
class DatabaseForMuckUserProvider implements UserProvider
{

    private $muckConnection;

    public function __construct(MuckConnection $muckConnection)
    {
        $this->muckConnection = $muckConnection;
    }

    //region Retrieval

    /**
     * Gets a base query that contains the required columns for creating a User object.
     * @return Builder
     */
    protected function getRetrievalQuery(): Builder
    {
        return DB::table('accounts')
            ->select('accounts.*', 'account_emails.verified_at')
            ->leftJoin('account_emails', 'account_emails.email', '=', 'accounts.email');
    }

    //Used when user is logged in, called with accountId (aid)
    public function retrieveById($identifier)
    {
        Log::debug('UserProvider RetrieveById attempt for ' . $identifier);
        //Retrieve account details from database first
        $accountQuery = $this->getRetrievalQuery()
            ->where('accounts.aid', $identifier)
            ->first();
        if (!$accountQuery) return null;
        $user = User::fromDatabaseResponse($accountQuery);
        //See if a character is saved by the session - this may be overridden later by the present page
        $characterDbref = session('lastCharacterDbref');
        if ($characterDbref && $user->characters()->has($characterDbref)) {
            $user->setCharacter($user->characters()[$characterDbref]);
        }
        Log::debug('UserProvider RetrieveById result for ' . $identifier . ', result = ' . $user->getAid());
        return $user;
    }

    public function retrieveByToken($identifier, $token)
    {
        Log::debug('UserProvider RetrieveByToken attempt for ' . $identifier . ':' . $token);
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
        Log::debug('UserProvider RetrieveByCredentials attempt for ' . json_encode($credentials));
        //If it's an email address we can try the database
        if (array_key_exists('email', $credentials) && strpos($credentials['email'], '@')) {
            $accountQuery = $this->getRetrievalQuery()
                ->where('accounts.email', $credentials['email'])
                ->first();
            if ($accountQuery) return User::fromDatabaseResponse($accountQuery);
        }

        //If it's an email that might be a character name or an api_token we try the muck
        if (
            (array_key_exists('email', $credentials) && !strpos($credentials['email'], '@'))
            || array_key_exists('api_token', $credentials)
        ) {
            $lookup = $this->muckConnection->retrieveByCredentials($credentials);
            if ($lookup) {
                list($aid, $character) = $lookup;
                $accountQuery = $this->getRetrievalQuery()
                    ->where('accounts.aid', $aid)
                    ->first();
                if (!$accountQuery) return null; //Account referenced by muck but wasn't found in DB!
                $user = User::fromDatabaseResponse($accountQuery);
                session(['lastCharacterDbref' => $character->getDbref()]);
                $user->setCharacter($character);
                return $user;
            }
        }

        return null;
    }

    /**
     * Function to find an account by any email, not just the primary one.
     * @param string $email
     * @return int|null AccountID
     */
    public function retrieveByAnyEmail(string $email): ?User
    {
        $accountId = DB::table('account_emails')
            ->where('account_emails.email', '=', $email)
            ->value('aid');

        if ($accountId)
            return $this->retrieveById($accountId);

        return null;
    }

    //endregion Retrieval

    /**
     * Retrieves properties that effect web views
     * @param User $user
     */
    public function loadLatePropertiesFor(User $user)
    {
        $preferences = DB::table('account_properties')
            ->where('aid', $user->getAid())
            ->whereIn('propname', ['webNoAvatars', 'webUseFullWidth', 'tos-hash-viewed'])
            ->get();
        foreach ($preferences as $preference) {
            switch (strtolower($preference->propname)) {
                case 'webnoavatars':
                    $user->setPrefersNoAvatars($preference->propdata == 'Y');
                    break;
                case 'webusefullwidth':
                    $user->setPrefersFullWidth($preference->propdata == 'Y');
                    break;
                case 'tos-hash-viewed':
                    $user->setAgreedToTermsOfService($preference->propdata == TermsOfService::getTermsOfServiceHash());
                    break;
            }
        }
    }

    public function loadRolesFor(User $user)
    {
        $row = DB::table('account_roles')
            ->where('aid', $user->getAid())
            ->first();
        $user->setRoles($row ? explode(',', $row->roles) : []);
    }

    /**
     * @param User $user
     * @param string $token
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        DB::table('accounts')
            ->where('aid', $user->getAid())
            ->update([$user->getRememberTokenName() => $token]);
    }


    /**
     * Validate a user against the given credentials.
     *
     * @param User $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials($user, array $credentials): bool
    {
        // return Hash::check($credentials['password'], $user->getAuthPassword());
        Log::debug('UserProvider ValidateCredentials for ' . $user->getAid() . ' with ' . json_encode($credentials));
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
     */
    public function createAccount(string $email, string $password): User
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
    public function updateEmail(User $user, string $email): bool
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

    public function isEmailAvailable(string $email): bool
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
     * @return Collection
     */
    public function getEmails(User $user): Collection
    {
        return DB::table('account_emails')->select([
            'email', 'created_at', 'verified_at'
        ])->where([
            'aid' => $user->getAid()
        ])->get();
    }

    // endregion Email

    public function getCharacters(User $user): Collection
    {
        return $this->muckConnection->getCharactersOf($user->getAid());
    }

    // region Properties

    public function getAccountProperty(User $user, string $property)
    {
        $row = DB::table('account_properties')
            ->where(['aid' => $user->getAid(), 'propname' => $property])
            ->first();
        if (!$row) return null;
        switch ($row->proptype) {
            case 'INTEGER':
                return (int)$row->propdata;
            case 'FLOAT':
                return (float)$row->propdata;
            case 'OBJECT':
                return new MuckDbref($row->propdata);
            // Other values are 'STRING'
            default:
                return $row->propdata;
        }
    }

    public function setAccountProperty(User $user, string $propertyName, $propertyValue)
    {
        $propertyType = null;
        switch (gettype($propertyValue)) {
            case 'integer':
                $propertyType = 'INTEGER';
                break;
            case 'double':
                $propertyType = 'FLOAT';
                break;
            case 'string':
                $propertyType = 'STRING';
                break;
            case 'boolean':
                $propertyType = 'STRING';
                $propertyValue = $propertyValue ? 'Y' : 'N';
                break;
            case 'object':
                if (is_a($propertyValue, MuckDbref::class)) {
                    $propertyType = 'Object';
                    $propertyValue = $propertyValue->toInt();
                } else throw new Error('Attempt to set account property to unknown value: ' . $propertyValue);
                break;
            default:
                throw new Error('Unknown property type to save: ' . typeof($propertyValue));
        }
        DB::table('account_properties')->updateOrInsert(
            ['aid' => $user->getAid(), 'propname' => $propertyName],
            ['propdata' => $propertyValue, 'proptype' => $propertyType]
        );

    }

    public function updateTermsOfServiceAgreement(User $user, string $hash)
    {
        $this->setAccountProperty($user, 'tos-hash-viewed', $hash);
    }

    public function updatePrefersNoAvatars(User $user, bool $value)
    {
        $this->setAccountProperty($user, 'webnoavatars', $value);
    }

    public function updatePrefersFullWidth(User $user, bool $value)
    {
        $this->setAccountProperty($user, 'webusefullwidth', $value);
    }

    #endregion Properties
}
