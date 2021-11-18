<?php

namespace App\Http\Controllers;

use App\SupportTickets\SupportTicketService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function showUserHome(SupportTicketService $service): View
    {
        return view('support.user.home')->with([
            'ticketsUrl' => route('support.user.tickets'),
            'categoryConfiguration' => $service->getCategoryConfiguration()
        ]);
    }

    public function showAgentHome(SupportTicketService $service): View
    {
        return view('support.agent.home')->with([
            'ticketsUrl' => route('support.agent.tickets'),
            'categoryConfiguration' => $service->getCategoryConfiguration()
        ]);
    }

    public function getUpdatedAt(SupportTicketService $service, int $id): Carbon
    {
        return $service->getLastUpdatedById($id);
    }

    public function getUserTickets(SupportTicketService $service): array
    {
        /** @var User $user */
        $user = auth()->user();

        return array_map(function ($ticket) use ($user) {
            return $ticket->serializeForUserListing($user);
        }, $service->getActiveTicketsForUser($user));
    }

    public function getAgentTickets(SupportTicketService $service): array
    {
        /** @var User $user */
        $user = auth()->user();

        return array_map(function ($ticket) use ($service, $user) {
            return $ticket->serializeForAgentListing($user);
        }, $service->getActiveTickets());
    }

    public function showAgentTicket(SupportTicketService $service, int $id): View
    {
        $ticket = $service->getTicketById($id);
        if (!$ticket) abort(404);

        /** @var User $user */
        $user = auth()->user();
        $character = $user->getStaffCharacter();

        return view('support.agent.ticket', [
            'ticket' => $ticket->serializeForAgent($service),
            'pollUrl' => route('support.getUpdatedAt', ['id' => $ticket->id]),
            'updateUrl' => route('support.agent.ticket', ['id' => $ticket->id]),
            'categoryConfiguration' => $service->getCategoryConfiguration(),
            'staffCharacter' => $character?->name()
        ]);
    }

    public function showUserTicket(SupportTicketService $service, int $id): View
    {
        $ticket = $service->getTicketById($id);
        if (!$ticket) abort(404);

        return view('support.user.ticket', [
            'ticket' => $ticket->serializeForUser($service),
            'pollUrl' => route('support.getUpdatedAt', ['id' => $ticket->id]),
            'updateUrl' => route('support.user.ticket', ['id' => $ticket->id]),
            'categoryConfiguration' => $service->getCategoryConfiguration()
        ]);

    }

    // Returns new representation of the ticket on success
    public function handleAgentUpdate(Request $request, SupportTicketService $service, int $id): array
    {
        $ticket = $service->getTicketById($id);
        if (!$ticket) abort(404, "Ticket doesn't exist.");

        /** @var User $user */
        $user = auth()->user();
        $character = $user->getStaffCharacter();

        if (!$character) abort(401, "No staff character associated with connection.");

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

            if ($task == 'TakeTicket') {
                $foundSomething = true;
                $service->setAgent($ticket, $user, $user->getStaffCharacter());
            }

            if ($task == 'AbandonTicket') {
                $foundSomething = true;
                $service->setAgent($ticket, null);
            }

            if ($task == 'RemoveWatcher') {
                $foundSomething = true;
                $service->removeWatcher($ticket, $user);
            }

            if ($task == 'AddWatcher') {
                $foundSomething = true;
                $service->addWatcher($ticket, $user);
            }

            if ($task == 'AddPublicNote' && $request->has('muck_content')) {
                $foundSomething = true;
                $service->addNote($ticket, $request->get('muck_content'), true, $user, $character);
            }

            if ($task == 'AddPrivateNote' && $request->has('muck_content')) {
                $foundSomething = true;
                $service->addNote($ticket, $request->get('muck_content'), false, $user, $character);
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
