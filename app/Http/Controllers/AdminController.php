<?php

namespace App\Http\Controllers;

use App\Admin\LogManager;

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

    public function getLogForDate(string $date)
    {
        return response()->file(LogManager::getLogFilePathForDate($date));
    }
}
