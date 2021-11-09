<?php

namespace App;

use App\Muck\MuckCharacter;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use JetBrains\PhpStorm\ArrayShape;
use MuckInterop;

/**
 * Class User
 * This represents a logged-in user, so contains present account and present player if set
 */
class User implements Authenticatable, MustVerifyEmail
{
    use Notifiable;

    protected int $aid;

    protected ?string $email = null; // Primary Email

    /** @var array<string, array>|null  */
    protected ?array $emails = null;

    protected ?string $password = null;
    protected ?string $passwordType = null;
    protected ?string $rememberToken = null;

    protected ?MuckCharacter $character = null; // Active Character

    // These are public since they're not stored past the request
    public ?Carbon $createdAt = null;
    public ?Carbon $updatedAt = null;
    public ?Carbon $lockedAt = null;
    public bool $emailVerified = false; // Only for primary email. Temporary.

    /**
     * Characters of this user. Null if they haven't been loaded yet.
     * @var array<int, MuckCharacter>|null
     */
    private ?array $characters = null;

    public function __construct(int $accountId)
    {
        $this->aid = $accountId;
    }

    public function __toString()
    {
        return "User#" . $this->aid;
    }

    /**
     * Checks whether two user objects share the same accountId
     * @param User $otherUser
     * @return bool
     */
    public function is(User $otherUser) : bool
    {
        return $this->aid === $otherUser->getAid();
    }

    public function getAdminUrl() : string
    {
        return route('admin.account', ['accountId' => $this->getAid()]);
    }

    public static function fromDatabaseResponse(\stdClass $query): User
    {
        if (
            !property_exists($query, 'aid') ||
            !property_exists($query, 'email') ||
            !property_exists($query, 'password')
        ) {
            throw new \InvalidArgumentException('Database response must at least contain aid, password and email');
        }
        $user = new self(intval($query->aid));
        $user->email = $query->email;
        $user->password = $query->password;
        if (property_exists($query, 'password_type')) $user->passwordType = $query->password_type;
        if (property_exists($query, 'verified_at') && $query->verified_at) $user->emailVerified = true;
        if (property_exists($query, 'created_at') && $query->created_at) $user->createdAt = new Carbon($query->created_at);
        if (property_exists($query, 'updated_at') && $query->updated_at) $user->updatedAt = new Carbon($query->updated_at);
        if (property_exists($query, 'locked_at') && $query->locked_at) $user->lockedAt = new Carbon($query->locked_at);
        return $user;
    }

    /**
     * Get expected user provider
     * @return DatabaseForMuckUserProvider
     */
    public static function getProvider(): DatabaseForMuckUserProvider
    {
        $guard = auth()->guard('account');
        return $guard->getProvider();
    }

    /**
     * Utility function to lookup user
     * @param $id
     * @return User|null
     */
    public static function find($id): ?User
    {
        return self::getProvider()->retrieveById($id);
    }

    /**
     * Utility function to lookup user by email.
     * If true is passed to $allowAlternative will return any match, otherwise will only return primary emails.
     * @param string $email
     * @param bool $allowAlternative
     * @return User|null
     */
    public static function findByEmail(string $email, bool $allowAlternative = false): ?User
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
    public function getAuthIdentifierName(): string
    {
        return 'AccountId';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return int | null
     */
    public function getAuthIdentifier(): ?int
    {
        return $this->aid;
    }

    public function getAid(): ?int
    {
        return $this->aid;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getPasswordType(): string
    {
        return $this->passwordType;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string|null
     */
    public function getRememberToken(): ?string
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

    #region Characters

    /**
     * @return MuckCharacter|null
     */
    public function getCharacter(): ?MuckCharacter
    {
        return $this->character;
    }

    /**
     * Just returns present character if they're staff.
     * Intending to one day potentially extend this to allow staff to tag a particular character as their staff one.
     * @return MuckCharacter|null
     */
    public function getStaffCharacter(): ?MuckCharacter
    {
        return $this->character?->isStaff() ? $this->character : null;
    }

    public function getCharacterDbref(): ?int
    {
        if (!$this->character) return null;
        return $this->character->dbref();
    }

    public function getCharacterName(): ?string
    {
        if (!$this->character) return null;
        return $this->character->name();
    }

    public function setCharacter(MuckCharacter $character)
    {
        $this->character = $character;
    }

    /**
     * @return array<int,MuckCharacter>
     */
    public function getCharacters(): array
    {
        if (!$this->characters) $this->characters = $this->getProvider()->getCharacters($this);
        return $this->characters;
    }
    #endregion Characters

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->emailVerified;
    }

    /**
     * Mark the given user's email as verified.
     * This will also make sure the given email is the user's primary email
     *
     * @return bool
     */
    public function markEmailAsVerified(): bool
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
    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    /**
     * @return array Array [email:[created_at, verified_at, primary]]
     */
    public function getEmails(): array
    {
        if (is_null($this->emails)) {
            $emails = $this->getProvider()->getEmails($this);
            $this->emails = $emails;
        }
        return $this->emails;
    }

    //Used by notifiable
    public function getKey(): ?int
    {
        return $this->aid;
    }

    public function setPassword(string $password)
    {
        $password = MuckInterop::createSHA1SALTPassword($password);
        $this->password = $password;
        $this->passwordType = 'SHA1SALT';
        $this->getProvider()->updatePassword($this, $password, 'SHA1SALT');
        //$this->updateLastUpdated(); //Done automatically with update
    }

    public function setEmail(string $email)
    {
        $this->emailVerified = $this->getProvider()->updateEmail($this, $email);
        $this->email = $email;
    }

    /**
     * @return Carbon|null
     */
    public function getLastConnect(): ?Carbon
    {
        return $this->getProvider()->getAccountLastConnect($this);
    }

    public function getReferralCount(): int
    {
        return $this->getProvider()->getReferralCount($this);
    }

    #region Admin functionality
    public function getAccountNotes(): array
    {
        return $this->getProvider()->getAccountNotes($this);
    }

    #[ArrayShape([
        'id' => "int|null",
        'created' => "\Carbon\Carbon|null",
        'characters' => "array",
        'notes' => "\App\Admin\AccountNote[]",
        'referrals' => "int",
        'lastConnected' => "\Carbon\Carbon|null",
        'emails' => "array",
        'primary_email' => "null|string",
        'roles' => 'array',
        'locked' => '\Carbon\Carbon|null',
        'url' => "string"
    ])]
    public function toAdminArray(): array
    {
        $characters = [];
        foreach ($this->getCharacters() as $character) {
            array_push($characters, $character->toArray());
        }

        $this->loadRolesIfRequired();

        return [
            'id' => $this->getAid(),
            'created' => $this->createdAt,
            'characters' => $characters,
            'notes' => $this->getAccountNotes(),
            'referrals' => $this->getReferralCount(),
            'lastConnected' => $this->getLastConnect(),
            'emails' => $this->getEmails(),
            'primary_email' => $this->email,
            'roles' => $this->roles,
            'locked' => $this->lockedAt,
            'url' => $this->getAdminUrl()
        ];
    }
    #endregion Admin functionality

    #region Late Loading Properties
    // These are loaded late because they're not required for api calls.
    protected bool $latePropertiesLoaded = false;
    protected bool $agreedToTermsOfService = false;
    protected bool $prefersNoAvatars = false;
    protected bool $prefersFullWidth = false;

    public function ensureLatePropertiesAreLoaded()
    {
        if (!$this->latePropertiesLoaded) {
            $this->getProvider()->loadLatePropertiesFor($this);
            $this->latePropertiesLoaded = true;
        }
    }

    public function getAgreedToTermsOfService(): bool
    {
        $this->ensureLatePropertiesAreLoaded();
        return $this->agreedToTermsOfService;
    }

    public function setAgreedToTermsOfService($value)
    {
        $this->agreedToTermsOfService = $value;
    }

    public function getPrefersNoAvatars(): bool
    {
        $this->ensureLatePropertiesAreLoaded();
        return $this->prefersNoAvatars;
    }

    public function setPrefersNoAvatars($value)
    {
        $this->prefersNoAvatars = $value;
        if ($this->latePropertiesLoaded) $this->getProvider()->updatePrefersNoAvatars($this, $value);
    }

    public function getPrefersFullWidth(): bool
    {
        $this->ensureLatePropertiesAreLoaded();
        return $this->prefersFullWidth;
    }

    public function setPrefersFullWidth($value)
    {
        $this->prefersFullWidth = $value;
        if ($this->latePropertiesLoaded) $this->getProvider()->updatePrefersFullWidth($this, $value);
    }

    public function storeTermsOfServiceAgreement($hash)
    {
        $this->getProvider()->updateTermsOfServiceAgreement($this, $hash);
    }
    #endregion Late Loading Properties

    #region Account Properties
    public function getAccountProperty(string $property)
    {
        return self::getProvider()->getAccountProperty($this, $property);
    }

    public function setAccountProperty(string $property, $value)
    {
        self::getProvider()->setAccountProperty($this, $property, $value);
    }
    #endregion Account Properties

    #region Roles
    protected ?array $roles = null;

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function hasRole(string $role): bool
    {
        $this->loadRolesIfRequired();

        return in_array($role, $this->roles)
            || in_array('admin', $this->roles) //Admin role has every role
            || ($role == 'staff' && $this->character && $this->character->isStaff()); // Staff characters are staff
    }

    private function loadRolesIfRequired()
    {
        if ($this->roles == null) $this->getProvider()->loadRolesFor($this);
    }
    #endregion Roles

}
