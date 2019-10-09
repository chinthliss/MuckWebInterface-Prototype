<?php

namespace App\Http\Controllers;

use App\Contracts\MuckConnectionContract;
use App\Muck\MuckCharacter;
use Illuminate\Http\Request;
use App\Muck;

class HomeController extends Controller
{
    public function show(MuckConnectionContract $muck)
    {
        //dd($muck->getCharacters()->toArray());
        return view('home')->with([
            'characters' => $muck->getCharacters()->map(function (MuckCharacter $item) { return $item->toArray(); })
        ]);
    }
}
