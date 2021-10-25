<?php

namespace App\Http\Controllers;

use App\SupportTickets\SupportTicketService;
use App\SupportTickets\SupportTicket;
use App\User;
use Illuminate\View\View;

class SupportTicketController extends Controller
{

    public function showUserHome() : View
    {
        return view('support.user.home')->with([
            'ticketsUrl' => route('support.user.tickets')
        ]);
    }

    public function showAgentHome() : View
    {
        return view('support.agent.home')->with([
            'ticketsUrl' => route('support.agent.tickets')
        ]);
    }

    public function getUserTickets(SupportTicketService $service) : array
    {
        /** @var User $user */
        $user = auth()->user();

        return array_map(function($ticket) use ($user) {
            $array = [
                'id' => $ticket->id,
                'url' => route('support.user.ticket', ['id' => $ticket->id]),
                'category' => $ticket->category,
                'title' => $ticket->title,
                'status' => ucfirst($ticket->status),
                'lastUpdatedAt' => $ticket->updatedAt,
                'lastUpdatedAtTimespan' => $ticket->updatedAt->diffForHumans(),
                'isPublic' => $ticket->isPublic
            ];
            //Only provide account ID if it's the users
            if ($ticket->user == $user) $array['user'] = $ticket->user->getAid();
            if ($ticket->character) $array['character'] = $ticket->character->name();
            return $array;
        },$service->getActiveTicketsForUser($user));
    }

    public function getAgentTickets(SupportTicketService $service) : array
    {
        return array_map(function($ticket) use ($service) {

            $working = [];
            foreach ($service->getSubscriptions($ticket) as $subWho => $subType) {
                $subscriber = User::find($subWho);
                if ($subWho && $subType == 'work') $working[] = $subscriber->getAid();
            }

            $array = [
                'id' => $ticket->id,
                'url' => route('support.agent.ticket', ['id' => $ticket->id]),
                'category' => $ticket->category,
                'title' => $ticket->title,
                'status' => ucfirst($ticket->status),
                'lastUpdatedAt' => $ticket->updatedAt,
                'lastUpdatedAtTimespan' => $ticket->updatedAt->diffForHumans(),
                'isPublic' => $ticket->isPublic,
                'working' => $working
            ];
            if ($ticket->user) $array['user'] = $ticket->user->getAid();
            if ($ticket->character) $array['character'] = $ticket->character->name();
            return $array;
        },$service->getActiveTickets());
    }

    public function showAgentTicket(SupportTicketService $service, int $id) : View
    {
        $ticket = $service->getTicketById($id);

        if (!$ticket) abort(404);

        $output = [
            'id' => $ticket->id,
            'url' => route('support.agent.ticket', ['id' => $ticket->id]),
            'category' => $ticket->category,
            'title' => $ticket->title,
            'content' => $ticket->content,
            'createdAt' => $ticket->createdAt,
            'createdAtTimespan' => $ticket->createdAt->diffForHumans(),
            'status' => ucfirst($ticket->status),
            'statusAt' => $ticket->statusAt,
            'statusAtTimespan' => $ticket->statusAt->diffForHumans(),
            'closedAt' => $ticket->closedAt,
            'closedAtTimespan' => $ticket->closedAt ? $ticket->closedAt->diffForHumans() : null,
            'closureReason' => ucfirst($ticket->closureReason),
            'isPublic' => $ticket->isPublic,
            'updatedAt' => $ticket->updatedAt,
            'updatedAtTimespan' => $ticket->updatedAt->diffForHumans()
        ];
        if ($ticket->user) $output['requesterAccountId'] = $ticket->user->getAid();
        if ($ticket->character) {
            $output['requesterCharacterDbref'] = $ticket->character->dbref();
            $output['requesterCharacterName'] = $ticket->character->name();
        }

        $output['log'] = array_map(function($entry) {
            return $entry->toAdminArray();
        }, $service->getLog($ticket));

        $output['links_from'] = [];
        $output['links_to'] = [];
        foreach ($service->getLinks($ticket) as $link) {
            if ($link->from->id == $ticket->id)
                $output['links_to'][] = $link->toAgentArray();
            else
                $output['links_from'][] = $link->toAgentArray();
        }

        $output['watchers'] = [];
        $output['workers'] = [];
        foreach ($service->getSubscriptions($ticket) as $accountId=>$type) {
            $subscription = [
                'accountId' => $accountId,
                'url' => route('admin.account', ['accountId' => $accountId])
            ];
            if ($type == 'work')
                $output['workers'][] = $subscription;
            else
                $output['watchers'][] = $subscription;
        }

        return view('support.agent.ticket', [
            'ticket' => $output
        ]);
    }

}
