<?php

namespace App\Http\Controllers;

use App\Muck\MuckCharacter;
use App\Muck\MuckConnection;
use App\SupportTickets\SupportTicket;
use App\SupportTickets\SupportTicketService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function showUserHome(SupportTicketService $service): View
    {
        return view('support.user.home')->with([
            'ticketsUrl' => route('support.user.tickets'),
            'categoryConfiguration' => $service->getCategoryConfiguration(),
            'newTicketUrl' => route('support.user.new')
        ]);
    }

    public function showAgentHome(SupportTicketService $service): View
    {
        return view('support.agent.home')->with([
            'ticketsUrl' => route('support.agent.tickets'),
            'categoryConfiguration' => $service->getCategoryConfiguration(),
            'newTicketUrl' => route('support.agent.new')
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
            'userUrl' => route('support.user.ticket', ['id' => $ticket->id]),
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

        /** @var User $user */
        $user = auth()->user();

        return view('support.user.ticket', [
            'ticket' => $ticket->serializeForUser($service, $user),
            'pollUrl' => route('support.getUpdatedAt', ['id' => $ticket->id]),
            'updateUrl' => route('support.user.ticket', ['id' => $ticket->id]),
            'categoryConfiguration' => $service->getCategoryConfiguration()
        ]);

    }

    // Returns true if found something
    private function processUpdate(Request $request, SupportTicketService $service, SupportTicket $ticket,
                                   ?User   $user, ?MuckCharacter $character): bool
    {
        $foundSomething = false;
        $isStaff = $character->isStaff();

        if ($request->has('title') && $isStaff) {
            $foundSomething = true;
            $service->setTitle($ticket, $request->get('title'), $user, $character);
        }

        if ($request->has('category') && $isStaff) {
            $foundSomething = true;
            $service->setCategory($ticket, $request->get('category'), $user, $character);
        }

        if ($request->has('status') && $isStaff) {
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

        if ($request->has('agent') && $isStaff) {
            $foundSomething = true;
            $character = resolve(MuckConnection::class)->getByPlayerName($request->get('agent'));
            if ($character && $character->isStaff()) {
                $service->setAgent($ticket, User::find($character->aid()), $character);
            } else {
                abort(400, "Either that character doesn't exist or they aren't staff.");
            }
        }

        // Things that aren't just changing values on the ticket
        if ($request->has('task')) {
            $task = $request->get('task');

            if ($task == 'TakeTicket' && $isStaff) {
                $foundSomething = true;
                $service->setAgent($ticket, $user, $user->getStaffCharacter());
            }

            if ($task == 'AbandonTicket' && $isStaff) {
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

            if ($task == 'AddPrivateNote' && $request->has('muck_content') && $isStaff) {
                $foundSomething = true;
                $service->addNote($ticket, $request->get('muck_content'), false, $user, $character);
            }

            if ($task == 'AddLink' && $request->has('to') && $request->has('type') && $isStaff) {
                $foundSomething = true;
                $ticketTo = $service->getTicketById($request->get('to'));
                $service->linkTickets($ticket, $ticketTo, $request->get('type'), $user, $character);
            }
        }

        return $foundSomething;
    }

    // Returns new representation of the ticket on success
    public function handleUserUpdate(Request $request, SupportTicketService $service, int $id): array
    {
        $ticket = $service->getTicketById($id);
        if (!$ticket) abort(404, "Ticket doesn't exist.");

        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();

        $foundSomething = $this->processUpdate($request, $service, $ticket, $user, $character);
        if (!$foundSomething) {
            abort(400, "Couldn't find anything to update in the request.");
        }

        return $ticket->serializeForUser($service, $user);
    }

    // Returns new representation of the ticket on success
    public function handleAgentUpdate(Request $request, SupportTicketService $service, int $id): array
    {
        $ticket = $service->getTicketById($id);
        if (!$ticket) abort(404, "Ticket doesn't exist.");

        /** @var User $user */
        $user = auth()->user();
        $character = $user->getStaffCharacter();

        if (!$character) abort(401, "You need to be logged in as a staff character to use this functionality.");

        $foundSomething = $this->processUpdate($request, $service, $ticket, $user, $character);
        if (!$foundSomething) {
            abort(400, "Couldn't find anything to update in the request.");
        }

        return $ticket->serializeForAgent($service);
    }

    #region Raising a ticket
    public function showUserRaiseTicket(SupportTicketService $service): View
    {
        return view('support.user.new')->with([
            'categoryConfiguration' => $service->getCategoryConfiguration()
        ]);
    }

    public function showAgentRaiseTicket(SupportTicketService $service): View
    {
        /** @var User $user */
        $user = auth()->user();
        $character = $user->getStaffCharacter();

        return view('support.agent.new')->with([
            'categoryConfiguration' => $service->getCategoryConfiguration(),
            'staffCharacter' => $character?->name()
        ]);
    }

    private function sharedRaiseTicketValidation(Request $request): array
    {
        $request->validate([
            'ticketCategoryCode' => 'required|max:80',
            'ticketTitle' => 'required|max:80',
            'ticketContent' => 'required'
        ], [
            'ticketCategoryCode.required' => 'You need to pick a category for the ticket.',
            'ticketTitle.required' => 'You must enter a short title for the ticket.',
            'ticketContent.required' => 'You must enter details for the ticket.'
        ]);

        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();

        return [
            'categoryCode' => $request->get('ticketCategoryCode'),
            'title' => $request->get('ticketTitle'),
            'content' => $request->get('ticketContent'),
            'user' => $user,
            'character' => $character
        ];

    }

    public function processUserRaiseTicket(Request $request, SupportTicketService $service)
    {
        $details = $this->sharedRaiseTicketValidation($request);

        $ticket = $service->createTicket($details['categoryCode'], $details['title'],
            $details['content'], $details['user'], $details['character']);

        return redirect()->route('support.user.ticket', ['id' => $ticket->id]);
    }

    public function processAgentRaiseTicket(Request $request, SupportTicketService $service, MuckConnection $muck)
    {
        $details = $this->sharedRaiseTicketValidation($request);

        $characterOverride = $request->get('ticketCharacter');
        if ($characterOverride) {
            $details['character'] = $muck->getByPlayerName($characterOverride);
            if (!$details['character'])
                throw ValidationException::withMessages(['ticketCharacter' => "Couldn't lookup the given name."]);
            $details['user'] = User::find($details['character']->aid());
        }

        $ticket = $service->createTicket($details['categoryCode'], $details['title'],
            $details['content'], $details['user'], $details['character']);

        return redirect()->route('support.agent.ticket', ['id' => $ticket->id]);
    }

    #endregion
}
