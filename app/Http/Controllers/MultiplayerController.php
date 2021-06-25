<?php

namespace App\Http\Controllers;

use App\Muck\MuckConnection;
use App\Notifications\MuckWebInterfaceNotification;
use App\User as User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

//For core multiplayer functionality only
class MultiplayerController extends Controller
{

    public function showMultiplayerDashboard()
    {
        /** @var User $user */
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

    #region Character Selection

    public function showCharacterSelect(MuckConnection $muck)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) abort(401);

        $characters = [];
        foreach ($user->getCharacters() as $character) {
            array_push($characters, $character->toArray());
        }

        $characterSlotState = $muck->getCharacterSlotState($user);

        return view('multiplayer.character-select')->with([
            "characters" => $characters,
            "characterSlotCount" => $characterSlotState['characterSlotCount'],
            "characterSlotCost" => $characterSlotState['characterSlotCost']
        ]);
    }

    public function buyCharacterSlot(MuckConnection $muck)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) abort(401);

        return $muck->buyCharacterSlot($user);
    }

    #endregion Character Selection

    #region Character Creation

    public function showCharacterCreation()
    {
        return view('multiplayer.character-create');
    }

    public function createCharacter(Request $request, MuckConnection $muck)
    {
        /** @var User $user */
        $user = auth()->user();

        $request->validate([
            'characterName' => 'required'
        ]);
        $desiredName = $request->input('characterName');
        $issue = $muck->findProblemsWithCharacterName($desiredName);
        if ($issue) throw ValidationException::withMessages(['characterName' => $issue]);

        try {
            $result = $muck->createCharacterForUser($desiredName, $user);
        }
        catch (Exception $e) {
            throw ValidationException::withMessages(['characterName' => $e->getMessage()]);
        }
        $user->setCharacter($result['character']);

        MuckWebInterfaceNotification::notifyUser($user,
            "Your new character '$desiredName' has been created with an initial password of: {$result['initialPassword']}");

        return redirect()->route('multiplayer.character.generate');
    }

    public function showCharacterGeneration()
    {
        return view('multiplayer.character-initial-setup');
    }

    #endregion Character Creation

    public function setActiveCharacter(Request $request, MuckConnection $muck)
    {
        /** @var User $user */
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


    public function showAvatarEditor()
    {
        return view('multiplayer.avatar');
    }
}
