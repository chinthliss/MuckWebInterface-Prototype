<?php

namespace App\Notifications;

use App\MuckWebInterfaceChannel;
use App\Muck\MuckCharacter;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Class MuckWebInterfaceNotification
 * @package App\Notifications
 */
class MuckWebInterfaceNotification extends Notification
{
    use Queueable;

    private string $message;

    private ?int $gameCode;

    private ?MuckCharacter $character;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $message, int $gameCode = null, MuckCharacter $character = null)
    {
        $this->gameCode = $gameCode;
        $this->character = $character;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return [MuckWebInterfaceChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable): array
    {
        return [
            'aid' => $notifiable->getAid(),
            'message' => $this->message,
            'game_code' => $this->gameCode,
            'character_dbref' => $this->character?->toInt()
        ];
    }

    public function character(): ?MuckCharacter
    {
        return $this->character;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function gameCode(): ?int
    {
        return $this->gameCode;
    }

    #region Utilities

    /**
     * Utility to send a message that will be visible on the account through any game
     * @param User $user
     * @param string $message
     */
    public static function notifyAccount(User $user, string $message)
    {
        $user->notify(new MuckWebInterfaceNotification($message));
    }

    /**
     * Utility to send a message that will be visible on any character on this game
     * @param User $user
     * @param string $message
     * @param int|null $gameCode Optional, defaults to this game.
     */
    public static function notifyUser(User $user, string $message, ?int $gameCode = null)
    {
        if (!$gameCode) $gameCode = config('muck.muck_code');
        $user->notify(new MuckWebInterfaceNotification($message, $gameCode));
    }

    /**
     * Utility to send a message that will be visible to a specific character on a game
     * @param User $user
     * @param MuckCharacter $character
     * @param string $message
     */
    public static function notifyCharacter(User $user, MuckCharacter $character, string $message)
    {
        $user->notify(new MuckWebInterfaceNotification($message, config('muck.muck_code'), $character));
    }

    public static function notifyUserOrCharacter(User $user, ?MuckCharacter $character, string $message)
    {
        if ($character)
            self::notifyCharacter($user, $character, $message);
        else
            self::notifyUser($user, $message);
    }
    #endregion Utilities
}
