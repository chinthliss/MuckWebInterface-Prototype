<?php

namespace App\Http\Controllers;

use App\Admin\LogManager;
use App\DatabaseForMuckUserProvider;
use App\Muck\MuckConnection;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    public function show()
    {
        return view('admin.home');
    }

    public function showLogViewer()
    {
        return view('admin.logviewer')->with([
            'dates' => LogManager::getDates()
        ]);
    }

    public function showAccount(int $accountId)
    {
        $user = User::find($accountId);
        if (!$user) abort(404);
        return view('admin.account')->with([
            'account' => $user->toAdminArray(),
            'muckName' => config('muck.muck_name')
        ]);
    }

    public function showAccountFinder()
    {
        return view('admin.accounts')->with([
            'apiUrl' => route('admin.accounts.api')
        ]);
    }

    public function findAccounts(Request $request, DatabaseForMuckUserProvider $userProvider)
    {
        $searchAccountId = $request->input('account');
        $searchCharacterName = $request->input('character');
        $searchEmail = $request->input('email');
        $searchCreationDate = $request->input('creationDate');

        if ( !$searchAccountId && !$searchCharacterName && !$searchEmail && !$searchCreationDate)
            abort(400);
        $results = [];

        if ($searchAccountId) {
            $user = $userProvider->retrieveById($searchAccountId);
            if ($user) $results[$user->getAid()] = $user;
        }

        if ($searchEmail) {
            $searchResults = $userProvider->searchByEmail($searchEmail);
            if (count($results))
                $results = array_intersect_key($results, $searchResults);
            else
                $results = $searchResults;
        }

        if ($searchCharacterName) {
            $searchResults = $userProvider->searchByCharacterName($searchCharacterName);
            if (count($results))
                $results = array_intersect_key($results, $searchResults);
            else
                $results = $searchResults;
        }

        if ($searchCreationDate) {
            $searchCreationDate = new Carbon($searchCreationDate);
            $searchResults = $userProvider->searchByCreationDate($searchCreationDate);
            if (count($results))
                $results = array_intersect_key($results, $searchResults);
            else
                $results = $searchResults;
        }

        $parsedResults = [];
        foreach ($results as $user) {
            array_push($parsedResults, $user->toAdminArray());
        }
        return $parsedResults;
    }

    public function getLogForDate(string $date)
    {
        return response()->file(LogManager::getLogFilePathForDate($date));
    }
}
