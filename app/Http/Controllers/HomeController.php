<?php

namespace App\Http\Controllers;

use App\Contracts\MuckConnection;
use App\Muck\MuckCharacter;
use Illuminate\Http\Request;
use App\Muck;

class HomeController extends Controller
{
    public function show(MuckConnection $muck)
    {
        //dd($muck->getCharacters()->toArray());
        return view('home')->with([
            'characters' => $muck->getCharacters()->map(function (MuckCharacter $item) { return $item->toArray(); })
        ]);
    }
}
