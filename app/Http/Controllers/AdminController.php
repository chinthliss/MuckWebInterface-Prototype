<?php

namespace App\Http\Controllers;

use App\Admin\LogManager;
use App\User;

class AdminController extends Controller
{
    public function show()
    {
        return view('admin/home');
    }

    public function showLogViewer()
    {
        return view('admin/logviewer')->with([
            'dates' => LogManager::getDates()
        ]);
    }

    public function showAccount(int $accountId)
    {
        $user = User::find($accountId);
        if (!$user) abort(404);
        return view('admin/account')->with([
            'account' => $user->toAdminArray()
        ]);
    }

    public function getLogForDate(string $date)
    {
        return response()->file(LogManager::getLogFilePathForDate($date));
    }
}
