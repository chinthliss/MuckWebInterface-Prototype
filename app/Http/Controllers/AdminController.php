<?php

namespace App\Http\Controllers;

use App\Admin\LogManager;
use App\DatabaseForMuckUserProvider;
use App\SupportTickets\SupportTicketService;
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

    public function getLogForDate(string $date)
    {
        return response()->file(LogManager::getLogFilePathForDate($date));
    }

    public function showAccount(int $accountId, SupportTicketService $supportTicketService)
    {
        $user = User::find($accountId);
        if (!$user) abort(404);

        $previousTickets = [];
        foreach($supportTicketService->getTicketsFromUser($user) as $ticket) {
            $formattedTicket = $ticket->serializeForAgentListing($user);
            $formattedTicket['categoryLabel'] =
                $supportTicketService->getCategory($ticket->categoryCode)?->name ?? 'Unknown';
            $previousTickets[] = $formattedTicket;
        }
        return view('admin.account')->with([
            'account' => $user->serializeForAdminComplete(),
            'muckName' => config('muck.muck_name'),
            'previousTickets' => $previousTickets
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
            array_push($parsedResults, $user->serializeForAdminComplete());
        }
        return $parsedResults;
    }

    public function showAccountRoles(DatabaseForMuckUserProvider $userProvider)
    {
        $roles = $userProvider->getAllRoles();
        $users = [];
        foreach ($roles as $role) {
            $users[] = $role->serializeForAdminComplete();
        }
        return view('admin.accountroles')->with([
            'users' => $users
        ]);
    }

}
