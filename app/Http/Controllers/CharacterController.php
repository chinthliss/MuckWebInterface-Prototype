<?php

namespace App\Http\Controllers;

use App\Muck\MuckConnection;

class CharacterController extends Controller
{
    public function show(MuckConnection $muck, string $characterName)
    {
        return view('character')->with([
            'character' => $characterName
        ]);
    }
}
