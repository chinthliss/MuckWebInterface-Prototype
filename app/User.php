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

    /**
     * Primary Email, null until checked
     * @var string|null
     */
    protected ?string $email = null;

    /**
     * All email, null until checked
     * @var array<string, array>|null
     */
    protected ?array $emails = null;

    protected ?string $password = null;
    protected ?string $passwordType = null;
    protected ?string $rememberToken = null;

    protected ?MuckCharacter $character = null; // Active Character, null until checked

    protected ?Carbon $lastConnect = null; // Null until checked

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
     * @param User|null $otherUser
     * @return bool
     */
    public function is(?User $otherUser): bool
    {
        return $this->aid === $otherUser?->getAid();
    }

    public function getAdminUrl(): string
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
    public function setRememberToken($value): void
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
        return $this->character?->dbref();
    }

    public function getCharacterName(): ?string
    {
        return $this->character?->name();
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
    public function sendEmailVerificationNotification(): void
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
        if (!$this->lastConnect) $this->lastConnect = $this->getProvider()->getAccountLastConnect($this);
        return $this->lastConnect;
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

    /**
     * Returns a simple representation of a User
     * @return array
     */
    #[ArrayShape([
        'id' => "int|null",
        'created' => "\Carbon\Carbon|null",
        'lastConnected' => "\Carbon\Carbon|null",
        'roles' => 'array',
        'locked' => '\Carbon\Carbon|null',
        'url' => "string"
    ])]
    public function serializeForAdmin(): array
    {
        $this->loadRolesIfRequired();

        return [
            'id' => $this->getAid(),
            'created' => $this->createdAt,
            'lastConnected' => $this->getLastConnect(),
            'roles' => $this->roles,
            'locked' => $this->lockedAt,
            'url' => $this->getAdminUrl()
        ];
    }

    /**
     * Produces an array to output for an Admin page.
     * Includes costly operations such as fetching characters and all emails
     * @return array
     */
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
    public function serializeForAdminComplete(): array
    {
        $characters = [];
        foreach ($this->getCharacters() as $character) {
            $characters[] = $character->toArray();
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

    #region Avatar viewing preference

    const AVATAR_PREFERENCE_HIDDEN   = 'hidden';   // Not used
    const AVATAR_PREFERENCE_CLEAN    = 'clean';    // No naughty bits
    const AVATAR_PREFERENCE_DEFAULT  = 'default';  // Female naughty bits
    const AVATAR_PREFERENCE_EXPLICIT = 'explicit'; // All the naughty bits

    protected ?string $avatarPreference = null; // Loaded on demand

    public function getAvatarPreference(): string
    {
        if ($this->avatarPreference === null) {
            $preference = $this->getAccountProperty('webAvatarPreference');
            $this->avatarPreference = $preference ?: self::AVATAR_PREFERENCE_DEFAULT;
        }

        return $this->avatarPreference;
    }

    public function setAvatarPreference(string $value): void
    {
        $this->avatarPreference = $value;
        $this->setAccountProperty( 'webAvatarPreference', $value);
    }

    #endregion Avatar viewing preference

    #region Terms of service
    protected ?bool $agreedToTermsOfService = null; // Loaded on demand

    public function getAgreedToTermsOfService(): bool
    {
        if ($this->agreedToTermsOfService === null) {
            $hash = $this->getAccountProperty('tos-hash-viewed');
            $this->agreedToTermsOfService = ($hash == TermsOfService::getTermsOfServiceHash());
        }
        return $this->agreedToTermsOfService;
    }

    public function setAgreedToTermsOfService(string $hash): void
    {
        $this->setAccountProperty('tos-hash-viewed', $hash);
    }

    #endregion Terms of service

    #region Account Properties
    public function getAccountProperty(string $property): mixed
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

    /**
     * Tests if a user has a role or counts as having a role
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        $this->loadRolesIfRequired();

        // Site admin has every role
        if (in_array('siteadmin', $this->roles)) return true;

        // Admin can be granted by the logged in character
        if ($role == 'admin') {
            return in_array('admin', $this->roles) || ($this->character && $this->character->isAdmin());
        }

        // Staff can be granted by the logged in character AND the admin role
        if ($role == 'staff') {
            return in_array('staff', $this->roles) || in_array('admin', $this->roles)
                || ($this->character && ($this->character->isAdmin() || $this->character->isStaff()));
        }

        //Normal handling
        return in_array($role, $this->roles)
            || in_array('siteadmin', $this->roles); //Site admin role has every role
    }

    private function loadRolesIfRequired()
    {
        if ($this->roles == null) $this->getProvider()->loadRolesFor($this);
    }

    /**
     * Shortcut to check if a user has a staff role.
     * This can be true on a non-permanent basis if they're logged in as a staff character
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    /**
     * Shortcut to check if a user has an admin role.
     * This can be true on a non-permanent basis if they're logged in as an admin character
     * @return bool
     */

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    //Shortcut to checking if the user has the site admin role
    public function isSiteAdmin(): bool
    {
        return $this->hasRole('siteadmin');
    }
    #endregion Roles

}
