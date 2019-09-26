<?php

namespace App;

use App\Helpers\MuckInterop;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Class User
 * This represents a logged in user, so contains present account and present player if set
 */
class User implements Authenticatable, MustVerifyEmail
{
    use Notifiable;

    protected $aid = null;
    protected $email = null;
    protected $password = null;
    protected $passwordType = null;
    protected $playerDbref = null;
    protected $rememberToken = null;
    // These are public since they don't save directly
    public $createdAt = null;
    public $updatedAt = null;
    public $emailVerified = false;
    public $playerName = '';

    public static function fromQuery(\stdClass $query)
    {
        if (
            !property_exists($query, 'aid') ||
            !property_exists($query, 'email') ||
            !property_exists($query, 'password')
        ) {
            throw new \InvalidArgumentException('Query result must at least contain aid, password and email');
        }
        $instance = new self();
        $instance->aid = $query->aid;
        $instance->email = $query->email;
        $instance->password = $query->password;
        if (property_exists($query, 'password_type')) $instance->passwordType = $query->password_type;
        if (property_exists($query, 'verified_at') && $query->verified_at) $instance->emailVerified = true;
        if (property_exists($query, 'created_at') && $query->created_at) $instance->createdAt = $query->created_at;
        if (property_exists($query, 'updated_at') && $query->updated_at) $instance->updatedAt = $query->updated_at;
        return $instance;
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'aid';
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

    public function playerDbref()
    {
        if (!$this->playerDbref && $newDbref = session('playerDbref'))
        {
            $this->playerDbref = $newDbref;
        }
        return $this->playerDbref;
    }

    public function playerName()
    {
        if (!$this->playerName && $newName = session('playerName'))
        {
            $this->playerName = $newName;
        }
        return $this->playerName;
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
        auth()->guard('account')->getProvider()->markEmailAsVerified($this, $this->email);
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
        auth()->guard('account')->getProvider()->updatePassword($this, $password, 'SHA1SALT');
        //$this->updateLastUpdated(); //Done automatically with update
    }

    public function setEmail(string $email)
    {
        $this->email = $email;
        auth()->guard('account')->getProvider()->updateEmail($this, $email);
    }
}
