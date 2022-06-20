<?php

namespace App\Http\Controllers;

use App\Avatar\AvatarService;
use App\Muck\MuckCharacter;
use App\Muck\MuckConnection;
use App\Muck\MuckObjectService;
use App\Notifications\MuckWebInterfaceNotification;
use App\User as User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

//For core multiplayer functionality only
class MultiplayerController extends Controller
{

    public function showMultiplayerDashboard() : View | RedirectResponse
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

    public function showCharacter(MuckConnection $muck, string $name) : View
    {
        /** @var User $user */
        $user = auth()->user();

        $character = $muck->getByPlayerName($name);
        if (!$character) abort(404);

        $avatarUrl = route('multiplayer.avatar.render', ['name' => $character->name()]);
        if ($user && $user->getAvatarPreference() === $user::AVATAR_PREFERENCE_HIDDEN) $avatarUrl = '';

        $profileUrl = route('multiplayer.character.api', ['name' => $character->name()]);

        return view('multiplayer.character')->with([
            'character' => $character,
            'avatarUrl' => $avatarUrl,
            'profileUrl' => $profileUrl,
            'controls' => $character->aid() === $user?->getAid() ? 'true' : 'false',
            'avatarWidth' => AvatarService::DOLL_WIDTH,
            'avatarHeight' => AvatarService::DOLL_HEIGHT
        ]);
    }

    public function getCharacterDetails(MuckConnection $muck, string $name)
    {
        $response = $muck->getProfileInformationForCharacterName($name);
        return $response;
    }

    public function getBadgesOf(MuckConnection $muck, string $name)
    {
        $response = $muck->getBadgesForCharacterName($name);
        return $response;

    }

    #region Character Selection

    public function showCharacterSelect(MuckConnection $muck) : View
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

    public function buyCharacterSlot(MuckConnection $muck) : JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) abort(401);

        return response()->json($muck->buyCharacterSlot($user));
    }

    #endregion Character Selection

    #region Character Creation

    public function showCharacterCreation() : View
    {
        return view('multiplayer.character-create');
    }

    /**
     * @throws ValidationException
     */
    public function createCharacter(Request $request, MuckConnection $muck) : RedirectResponse
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

    public function showCharacterGeneration(MuckConnection $muck): View | RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        //Because this page is available without an approved character, we need to manually check for an active character
        if (!$user->getCharacter()) return redirect(route('multiplayer.character.select'));

        $config  = $muck->getCharacterInitialSetupConfiguration($user);
        return view('multiplayer.character-initial-setup')->with([
            'config' => $config
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function finalizeCharacter(Request $request, MuckConnection $muck): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $request->validate([
            'gender' => 'required',
            'birthday' => 'required',
            'faction' => 'required'
        ], [
            'gender.required' => 'You need to select a starting gender.',
            'birthday.required' => 'You need to select a birthday.',
            'faction.required' => 'You need to select a faction.'
        ]);

        // Since the muck needs to do a final check on perks/flaws we're leaving validation of such to it
        $characterRequest = [
            'dbref' => $user->getCharacterDbref(),
            'gender' => $request->input('gender'),
            'birthday' => $request->input('birthday'),
            'faction' => $request->input('faction'),
            'perks' => $request->input('perks') ?? [],
            'flaws' => $request->input('flaws') ?? []
        ];
        $response = $muck->finalizeCharacter($characterRequest);

        if (!$response['success']) {
            throw ValidationException::withMessages(['other' => $response['messages']]);
        }

        return redirect()->route('multiplayer.gettingstarted');
    }
    #endregion Character Creation

    public function setActiveCharacter(Request $request, MuckObjectService $muckObjectService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user('account');
        if (!$user) abort(401);

        $dbref = $request->get('dbref');
        if (!$dbref) abort(400);

        /** @var MuckCharacter $character */
        $character = $muckObjectService->getByDbref($dbref);
        if ($character && $character->aid() == $user->getAid()) {
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

    public function showConnect() : View
    {
        return view('multiplayer.connect');
    }


    public function showChangeCharacterPassword(Request $request): View
    {
        /** @var User $user */
        $user = $request->user('account');
        $characters = [];
        foreach ($user->getCharacters() as $character) {
            array_push($characters, $character->toArray());
        }
        return view('multiplayer.character-change-password')->with([
            'characters' => $characters
        ]);
    }

    /**
     * @param Request $request
     * @param MuckConnection $muck
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function changeCharacterPassword(Request $request, MuckConnection $muck)
    {
        $request->validate([
            'accountpassword' => 'required',
            'password' => 'required',
            'character' => 'required'
        ], [
            'accountpassword.required' => 'You need to enter your Account Password.',
            'password.required' => 'You need to enter a new password to use.',
            'character.required' => 'You need to select a character.'
        ]);

        /** @var User $user */
        $user = auth()->user('account');
        if (!auth()->guard('account')->getProvider()->validateCredentials($user, ['password'=>$request['accountpassword']])) {
            throw ValidationException::withMessages(['accountpassword'=>["The provided password was incorrect."]]);
        }

        $characters = $user->getCharacters();
        /** @var MuckCharacter $character */
        $character = array_key_exists($request['character'], $characters) ? $characters[$request['character']] : null;
        if (!$character) {
            throw ValidationException::withMessages(['character'=>["The provided character was incorrect."]]);
        }

        $passwordIssues = $muck->findProblemsWithCharacterPassword($request['password']);
        if ($passwordIssues) throw ValidationException::withMessages(['password' => $passwordIssues]);

        $result = $muck->changeCharacterPassword($user, $character, $request['password']);
        if ($result) {
            $request->session()->flash('message-success', "The password for {$character->name()} was changed as requested. You can now use this password to logon via a telnet client.");
            return redirect(route('multiplayer.character.select'));
        }
        else throw ValidationException::withMessages(['character'=>["Something went wrong, if this continues please notify staff."]]);
    }

    public function showGettingStarted() : View
    {
        /** @var User $user */
        $user = auth()->user();
        $character = ($user ? $user->getCharacter() : null);

        return view('multiplayer.getting-started')->with([
            'hasAccount' => $user !== null,
            'hasAnyCharacter' => ($user && count($user->getCharacters()) > 0),
            'hasActiveCharacter' => $character !== null,
            'hasApprovedCharacter' => ($character && $character->isApproved()),
            'pageRecommendations' => [
                [
                    'page' => 'Perks',
                    'description' => "After character generation there are a load of additional perks you can pick up.",
                    'url' => 'TBC'
                ],
                [
                    'page' => 'Avatar',
                    'description' => "Control how your character appears to others on the website.",
                    'url' => 'TBC'
                ],
                [
                    'page' => 'Kinks',
                    'description' => "Set your preferences so others know how to interact with you.",
                    'url' => 'TBC'
                ]
            ]
        ]);
    }

    public function getWebsocketToken() {
        return '1A-FAKE';
    }
}
