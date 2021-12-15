<?php

namespace App\Listeners;

use App\Muck\MuckConnection;
use App\Notifications\MuckWebInterfaceNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendCopyOfNotificationToMuck
{
    private MuckConnection $muckConnection;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(MuckConnection $muckConnection)
    {
        $this->muckConnection = $muckConnection;
    }

    /**
     * Handle the event.
     *
     * @param  NotificationSent  $event
     * @return void
     */
    public function handle(NotificationSent $event)
    {
        if (!is_a($event, MuckWebInterfaceNotification::class)) return;
        if ($event->notification->gameCode() && $event->notification->gameCode() != config('muck.muck_code')) return;
        $this->muckConnection->externalNotification($event->notifiable,
            $event->notification->character(), $event->notification->message());
    }
}
