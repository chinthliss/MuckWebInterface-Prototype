<?php

namespace App;

use App\Helpers\MuckInterop;
use App\Muck\MuckCharacter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

/**
 * Class User
 * This represents a logged in user, so contains present account and present player if set
 */
class User implements Authenticatable, MustVerifyEmail
{
    use Notifiable;

    /**
     * @var int|null
     */
    protected $aid = null;

    protected $email = null; // Primary email
    protected $emails = null;
    protected $password = null;
    protected $passwordType = null;
    protected $rememberToken = null;

    /**
     * @var MuckCharacter|null
     */
    protected $character = null;

    // These are public since they're not stored past the request
    public $createdAt = null;
    public $updatedAt = null;
    public $emailVerified = false;

    /**
     * Characters of this user.
     * @var Collection(MuckCharacter)
     */
    private $characters = null;


    public static function fromDatabaseResponse(\stdClass $query)
    {
        if (
            !property_exists($query, 'aid') ||
            !property_exists($query, 'email') ||
            !property_exists($query, 'password')
        ) {
            throw new \InvalidArgumentException('Database response must at least contain aid, password and email');
        }
        $user = new self();
        $user->aid = intval($query->aid);
        $user->email = $query->email;
        $user->password = $query->password;
        if (property_exists($query, 'password_type')) $user->passwordType = $query->password_type;
        if (property_exists($query, 'verified_at') && $query->verified_at) $user->emailVerified = true;
        if (property_exists($query, 'created_at') && $query->created_at) $user->createdAt = $query->created_at;
        if (property_exists($query, 'updated_at') && $query->updated_at) $user->updatedAt = $query->updated_at;
        return $user;
    }

    /**
     * Get expected user provider
     * @return DatabaseForMuckUserProvider
     */
    public static function getProvider()
    {
        return auth()->guard('account')->getProvider();
    }

    /**
     * Utility function to lookup user
     * @param $id
     * @return User|null
     */
    public static function find($id) : ?User
    {
        return self::getProvider()->retrieveById($id);
    }

    /**
     * Utility function to lookup user by email.
     * If true is passed to $allowAlternative will return any match, otherwise will only return primary emails.
     * @param $email
     * @param false $allowAlternative
     * @return User|null
     */
    public static function findByEmail($email, $allowAlternative = false) : ?User
    {
        if ($allowAlternative)
            return self::getProvider()->retrieveByAnyEmail($email);
        else
            return self::getProvider()->retrieveByCredentials(['email' => $email]);
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'AccountId';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->aid;
    }

    public function getAid()
    {
        return $this->aid;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getPasswordType()
    {
        return $this->passwordType;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->rememberToken;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->rememberToken = $value;
    }

    public function getCharacterDbref()
    {
        if (!$this->character) return null;
        return $this->character->getDbref();
    }

    public function SetCharacter(MuckCharacter $character)
    {
        $this->character = $character;
    }


    public function getCharacterName()
    {
        if (!$this->character) return null;
        return $this->character->getName();
    }

    public function characters()
    {
        if (!$this->characters) $this->characters = $this->getProvider()->getCharacters($this);
        return $this->characters;
    }

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return $this->emailVerified;
    }

    /**
     * Mark the given user's email as verified.
     * This will also make sure the given email is the user's primary email
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        $this->getProvider()->markEmailAsVerified($this, $this->email);
        $this->emailVerified = true;
        return true;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new Notifications\VerifyEmail);
        //TODO - look at introducing work queue to handle notifications?
    }

    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getEmails()
    {
        if (!is_null($this->emails)) {
            return $this->emails;
        } else {
            $rawEmails = $this->getProvider()->getEmails($this);
            return $this->emails = $rawEmails->mapWithKeys(function ($item, $key) {
                $email = $item->email;
                unset($item->email);
                return [$email => $item];
            });
        }
    }

    //Used by notifiable
    public function getKey()
    {
        return $this->aid;
    }

    public function setPassword(string $password)
    {
        $password = MuckInterop::createSHA1SALTPassword($password);
        $this->password = $password;
        $this->password_type = 'SHA1SALT';
        $this->getProvider()->updatePassword($this, $password, 'SHA1SALT');
        //$this->updateLastUpdated(); //Done automatically with update
    }

    public function setEmail(string $email)
    {
        $this->emailVerified = $this->getProvider()->updateEmail($this, $email);
        $this->email = $email;
    }

    /**
     * @return MuckCharacter|null
     */
    public function getCharacter()
    {
        return $this->character;
    }

    //region Late Loading Properties
    // These are loaded late because they're not required for api calls.
    protected $latePropertiesLoaded = false;
    protected $agreedToTermsOfService = false;
    protected $prefersNoAvatars = false;
    protected $prefersFullWidth = false;

    public function ensureLatePropertiesAreLoaded()
    {
        if (!$this->latePropertiesLoaded) {
            $this->getProvider()->loadLatePropertiesFor($this);
            $this->latePropertiesLoaded = true;
        }
    }

    public function getAgreedToTermsOfService()
    {
        $this->ensureLatePropertiesAreLoaded();
        return $this->agreedToTermsOfService;
    }

    public function setAgreedToTermsOfService($value)
    {
        $this->agreedToTermsOfService = $value;
    }

    public function getPrefersNoAvatars()
    {
        $this->ensureLatePropertiesAreLoaded();
        return $this->prefersNoAvatars;
    }

    public function setPrefersNoAvatars($value)
    {
        $this->prefersNoAvatars = $value;
        if ($this->latePropertiesLoaded) $this->getProvider()->updatePrefersNoAvatars($this, $value);
    }

    public function getPrefersFullWidth()
    {
        $this->ensureLatePropertiesAreLoaded();
        return $this->prefersFullWidth;
    }

    public function setPrefersFullWidth($value)
    {
        $this->prefersFullWidth = $value;
        if ($this->latePropertiesLoaded) $this->getProvider()->updatePrefersFullWidth($this, $value);
    }
    //endregion Late Loading Properties

    public function storeTermsOfServiceAgreement($hash)
    {
        $this->getProvider()->updateTermsOfServiceAgreement($this, $hash);
    }

    #region Account Properties
    public function getAccountProperty(string $property)
    {
        return self::getProvider()->getAccountProperty($this, $property);
    }
    #endregion Account Properties

    #region Roles
    protected $roles = null;

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function hasRole(string $role): bool
    {
        if ($this->roles == null) $this->getProvider()->loadRolesFor($this);

        //Admin role has all other roles
        return in_array($role, $this->roles) || in_array('admin', $this->roles);
    }
    #endregion Roles

}
