<?php

namespace App\Http\Controllers;

use App\Muck\MuckConnection;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    public function show(MuckConnection $muck, string $characterName)
    {
        return view('character')->with([
            'character' => $characterName
        ]);
    }

    public function setActiveCharacter(Request $request, MuckConnection $muck)
    {
        $user = $request->user('account');
        if (!$user) abort(401);

        $dbref = $request->get('dbref');
        if (!$dbref) abort(400);

        $character = $muck->retrieveAndVerifyCharacterOnAccount($user, $dbref);
        if ($character) {
            // This is sufficient, middleware will set the cookie in the response
            $user->setCharacter($character);
            return 'success';
        }
        $request->session()->flash('message-success', 'Attempt to change character failed');
        return 'failure';
    }
}
