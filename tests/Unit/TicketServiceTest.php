<?php

namespace Tests\Unit;

use App\SupportTickets\SupportTicketService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateTicket()
    {
        $service = $this->app->make(SupportTicketService::class);
        $ticket = $service->createTicket('testCategory', 'testTitle,', 'testContent');
        $this->assertNotNull($ticket);
    }

    public function testGetTicketById()
    {
        $service = $this->app->make(SupportTicketService::class);
        $ticket = $service->createTicket('testCategory', 'testTitle,', 'testContent');

        $retrievedTicket = $service->getTicketById($ticket->id);
        $this->assertNotNull($retrievedTicket);
        $this->assertEquals($ticket->id, $retrievedTicket->id);
    }

    public function testGetActiveTickets()
    {
        $service = $this->app->make(SupportTicketService::class);
        $service->createTicket('testCategory', 'testTitle,', 'testContent');

        $tickets = $service->getActiveTickets();
        $this->assertNotNull($tickets);
        $this->assertNotEmpty($tickets);
    }

    public function testCloseTicket()
    {
        $service = $this->app->make(SupportTicketService::class);
        $ticket = $service->createTicket('testCategory', 'testTitle,', 'testContent');
        $service->closeTicket($ticket, 'completed');

        //Re-fetch to ensure it saved
        $ticket = $service->getTicketById($ticket->id);
        $this->assertNotNull($ticket->closedAt);
        $this->assertNotNull($ticket->closureReason);

        //Test second attempt to close it throws an exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("already closed");
        $service->closeTicket($ticket, 'completed');

    }

    public function testNotes()
    {
        $service = $this->app->make(SupportTicketService::class);
        $ticket = $service->createTicket('testCategory', 'testTitle,', 'testContent');
        $service->addNote($ticket, 'Test Public Note', true);
        $service->addNote($ticket, 'Test Staff Only Note', false);
        $log = $service->getLog($ticket);
        $privateEntry = null;
        $publicEntry = null;
        foreach ($log as $entry) {
            if ($entry->staffOnly) $privateEntry = $entry; else $publicEntry = $entry;
        }

        // Public entry
        $this->assertNotNull($publicEntry);
        $this->assertFalse($publicEntry->staffOnly);

        // Private entry
        $this->assertNotNull($privateEntry);
        $this->assertTrue($privateEntry->staffOnly);
    }

    public function testLinking()
    {
        $service = $this->app->make(SupportTicketService::class);
        $fromTicket = $service->createTicket('testCategory', 'testTitle,', 'testContent - From Ticket');
        $toTicket = $service->createTicket('testCategory', 'testTitle,', 'testContent - To ticket');
        $service->linkTickets($fromTicket, $toTicket, 'related');

        $fromLink = $service->getlinks($fromTicket)[0];
        $this->assertEquals($fromLink->from->id, $fromTicket->id);
        $this->assertEquals($fromLink->to->id, $toTicket->id);

        $toLink = $service->getLinks($toTicket)[0];
        $this->assertEquals($toLink->from->id, $fromTicket->id);
        $this->assertEquals($toLink->to->id, $toTicket->id);
    }

    public function testWatching()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $service = $this->app->make(SupportTicketService::class);
        $ticket = $service->createTicket('testCategory', 'testTitle,', 'testContent');

        $watchers = $service->getWatchers($ticket);
        $this->assertEmpty($watchers);

        $service->addWatcher($ticket, $user);
        $watchers = $service->getWatchers($ticket);
        $this->assertNotEmpty($watchers);

        $service->removeWatcher($ticket, $user);
        $watchers = $service->getWatchers($ticket);
        $this->assertEmpty($watchers);
    }

    public function testSetAgent()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $service = $this->app->make(SupportTicketService::class);
        $ticket = $service->createTicket('testCategory', 'testTitle,', 'testContent');

        $service->setAgent($ticket, $user);

        $ticket = $service->getTicketById($ticket->id);
        $this->assertEquals($ticket->agentUser, $user);
    }

    public function testHasVotedOnTicket()
    {
        $this->seed();
        $service = $this->app->make(SupportTicketService::class);
        $ticket = $service->createTicket('testCategory', 'testTitle,', 'testContent');

        $user = $this->loginAsValidatedUser();
        $this->assertNull($service->getVote($ticket, $user));
        $service->voteOn($ticket, 'up', $user);
        $this->assertNotNull($service->getVote($ticket, $user));

        // Make sure first doesn't interfere with a second
        $secondUser = $this->loginAsOtherValidatedUser();
        $this->assertNull($service->getVote($ticket, $secondUser));
        $service->voteOn($ticket, 'up', $secondUser);
        $this->assertNotNull($service->getVote($ticket, $secondUser));
    }

    public function testVoteOnTicket()
    {
        $this->seed();
        $service = $this->app->make(SupportTicketService::class);
        $ticket = $service->createTicket('testCategory', 'testTitle,', 'testContent');

        $user = $this->loginAsValidatedUser();
        $service->voteOn($ticket, 'up', $user);
        $this->assertEquals(1, $ticket->votesUp);
        $this->assertEquals(0, $ticket->votesDown);

        //Should be prevented from voting again
        $this->expectException(Exception::class);
        $service->voteOn($ticket, 'up', $user);
    }
}
