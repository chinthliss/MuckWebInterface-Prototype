<?php

namespace App\Http\Controllers;

use App\AccountNotificationManager;
use App\User;
use Illuminate\Http\Request;

class AccountNotificationsController extends Controller
{
    public function show()
    {
        return view('account-notifications')->with([
            'apiUrl' => route('account.notifications.api')
        ]);
    }

    public function getNotifications(AccountNotificationManager $notificationManager)
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$user) abort(401);

        $notifications = $notificationManager->getNotificationsFor($user);
        return array_map(function ($notification) {
            if ($notification['character']) $notification['character'] = $notification['character']->name();
            return $notification;
        }, $notificationManager->getNotificationsFor($user));
    }

    public function deleteNotification(AccountNotificationManager $notificationManager, int $id)
    {
        if (!$id) abort(419);

        /** @var User $user */
        $user = auth()->user();

        if (!$user) abort(401);

        $notification = $notificationManager->getNotification($id);

        if (!$notification) return null; // Maybe already deleted

        if ($notification->aid != $user->getAid()) abort(401);

        $notificationManager->deleteNotification($id);

        return $id;
    }

    public function deleteAllNotifications(Request $request, AccountNotificationManager $notificationManager)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) abort(401);

        $notificationManager->deleteAllNotificationsFor($user->getAid());

        return "OK";
    }

}
