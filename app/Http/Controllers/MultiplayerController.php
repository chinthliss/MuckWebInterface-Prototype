<?php

namespace App\Http\Controllers;

use App\Muck\MuckConnection;
use Illuminate\Http\Request;

//For core multiplayer functionality only
class MultiplayerController extends Controller
{

    public function showMultiplayerDashboard()
    {
        $user = auth()->user();

        $charactersToProcess = $user->getCharacters();
        if (count($charactersToProcess) === 0) //Redirect to create a character if we have no characters
            return redirect(route('multiplayer.character.select'));

        $characters = [];
        foreach ($charactersToProcess as $character) {
            array_push($characters, $character->toArray());
        }

        return view('multiplayer.home')->with([
            "characters" => $characters,
            "characterSelectUrl" => route('multiplayer.character.select')
        ]);
    }

    public function showCharacter(MuckConnection $muck, string $characterName)
    {
        return view('multiplayer.character')->with([
            'characters' => $characterName
        ]);
    }

    //Character select is a simple gate screen to pick a character.
    public function showCharacterSelect(MuckConnection $muck)
    {
        $user = auth()->user();

        $characters = [];
        foreach ($user->getCharacters() as $character) {
            array_push($characters, $character->toArray());
        }

        $characterSlotState = $muck->getCharacterSlotState();

        return view('multiplayer.character-select')->with([
            "characters" => $characters,
            "characterSlotCount" => $characterSlotState['characterSlotCount'],
            "characterSlotCost" => $characterSlotState['characterSlotCost']
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
            return response()->json([
                'status' => 'success',
                'redirectUrl' => redirect()->intended(route('multiplayer.home'))->getTargetUrl(),
                'message' => 'Login successful. Please refresh page.'
            ]);
        }
        $request->session()->flash('message-success', 'Attempt to change character failed');
        return response()->json([
            'status' => 'failure',
            'message' => 'Character change failed.'
        ]);

    }

    public function showAvatarEditor(Request $request)
    {
        return view('multiplayer.avatar');
    }
}
