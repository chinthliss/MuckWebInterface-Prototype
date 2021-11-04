<?php

namespace App\Http\Controllers;

use App\SupportTickets\SupportTicketService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    public function getUpdatedAt(SupportTicketService $service, int $id) : Carbon
    {
        return $service->getLastUpdatedById($id);
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
                'status' => $ticket->status,
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
                'status' => $ticket->status,
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

        return view('support.agent.ticket', [
            'ticket' => $ticket->serializeForAgent($service),
            'pollUrl' => route('support.getUpdatedAt', ['id' => $ticket->id]),
            'updateUrl' => route('support.agent.ticket', ['id' => $ticket->id]),
            'categoryConfiguration' => $service->getCategoryConfiguration()
        ]);
    }

    // Returns new representation of the ticket on success
    public function handleAgentUpdate(Request $request, SupportTicketService $service, int $id): array
    {
        $ticket = $service->getTicketById($id);
        if (!$ticket) abort(404);

        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();

        $foundSomething = false;

        if ($request->has('title')) {
            $foundSomething = true;
            $service->setTitle($ticket, $request->get('title'), $user, $character);
        }

        if ($request->has('category')) {
            $foundSomething = true;
            $service->setCategory($ticket, $request->get('category'), $user, $character);
        }

        if ($request->has('status')) {
            $foundSomething = true;
            if ($request->has('closureReason'))
                $service->closeTicket($ticket, $request->get('closureReason'), $user, $character);
            else
                $service->setStatus($ticket, $request->get('status'), $user, $character);
        }

        if ($request->has('isPublic')) {
            $foundSomething = true;
            $service->setPublic($ticket, $request->get('isPublic'), $user, $character);
        }

        // Things that aren't just changing values on the ticket
        if ($request->has('task')) {
            $task = $request->get('task');

            if ($task == 'RemoveMeAsWorker') {
                $foundSomething = true;
                $service->removeSubscription($ticket, $user, 'work');
            }

            if ($task == 'AddMeAsWorker') {
                $foundSomething = true;
                $service->addSubscription($ticket, $user, 'work');
            }

            if ($task == 'RemoveMeAsWatcher') {
                $foundSomething = true;
                $service->removeSubscription($ticket, $user, 'watch');
            }

            if ($task == 'AddMeAsWatcher') {
                $foundSomething = true;
                $service->addSubscription($ticket, $user, 'watch');
            }

            if ($task == 'AddPublicNote' && $request->has('content')) {
                $foundSomething = true;
                $service->addNote($ticket, $request->get('content'), true, $user, $character);
            }

            if ($task == 'AddPrivateNote' && $request->has('content')) {
                $foundSomething = true;
                $service->addNote($ticket, $request->get('content'), false, $user, $character);
            }

            if ($task == 'AddLink' && $request->has('to') && $request->has('type')) {
                $foundSomething = true;
                $ticketTo = $service->getTicketById($request->get('to'));
                $service->linkTickets($ticket, $ticketTo, $request->get('type'), $user, $character);
            }

        }

        if (!$foundSomething) {
            abort(400, "Couldn't find anything to update in the request.");
        }

        return $ticket->serializeForAgent($service);
    }

}
