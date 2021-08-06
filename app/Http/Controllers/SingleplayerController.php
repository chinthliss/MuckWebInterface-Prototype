<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SingleplayerController extends Controller
{

    public function showHome() : View
    {
        return view('singleplayer.home');
    }
}
