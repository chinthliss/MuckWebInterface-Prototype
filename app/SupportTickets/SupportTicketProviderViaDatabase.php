<?php

namespace App\SupportTickets;

use App\Muck\MuckDbref;
use App\User;
use App\Muck\MuckObjectService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SupportTicketProviderViaDatabase implements SupportTicketProvider
{
    private MuckObjectService $muckObjects;

    public function __construct(MuckObjectService $muckObjects)
    {
        $this->muckObjects = $muckObjects;
    }

    /**
     * @param object $row
     * @return SupportTicket
     */
    public function fromDatabaseRow(object $row): SupportTicket
    {
        $fromUser = null;
        if ($row->from_aid) $fromUser = User::find($row->from_aid);

        $fromCharacter = null;
        if ($row->from_muck_object_id) $fromCharacter = $this->muckObjects->getByMuckObjectId($row->from_muck_object_id);

        $agentUser = null;
        if ($row->agent_aid) $agentUser = User::find($row->agent_aid);

        $agentCharacter = null;
        if ($row->agent_muck_object_id) $agentCharacter = $this->muckObjects->getByMuckObjectId($row->agent_muck_object_id);

        return SupportTicket::createExisting(
            $row->id,
            $row->category,
            $row->title,
            $fromUser,
            $fromCharacter,
            $agentUser,
            $agentCharacter,
            new Carbon($row->created_at),
            $row->status,
            $row->status_at ? new Carbon($row->status_at) : null,
            new Carbon($row->updated_at),
            $row->closure_reason,
            $row->closed_at ? new Carbon($row->closed_at) : null,
            $row->public,
            $row->content
        );
    }

    /**
     * @inheritDoc
     */
    public function getById(int $id): ?SupportTicket
    {
        $ticket = null;
        $row = DB::table('tickets')
            ->where('id', '=', $id)
            ->first();
        if ($row) {
            $ticket = $this->fromDatabaseRow($row);
        }
        return $ticket;
    }

    /**
     * @inheritDoc
     */
    public function getByCategory(string $category): array
    {
        $tickets = [];
        $rows = DB::table('tickets')
            ->where('category', '=', $category)
            ->get();
        foreach ($rows as $row) {
            $ticket = $this->fromDatabaseRow($row);
            $tickets[] = $ticket;
        }
        return $tickets;
    }

    /**
     * @inheritDoc
     */
    public function getOpen(): array
    {
        $tickets = [];
        $rows = DB::table('tickets')
            ->whereNull('closed_at')
            ->get();
        foreach ($rows as $row) {
            $ticket = $this->fromDatabaseRow($row);
            $tickets[] = $ticket;
        }
        return $tickets;
    }

    /**
     * @inheritDoc
     */
    public function getActive(): array
    {
        $tickets = [];
        $rows = DB::table('tickets')
            ->whereNull('closed_at')
            ->orWhereDate('updated_at', '>', Carbon::now()->subDays(3))
            ->orderBy('updated_at', 'desc')
            ->get();
        foreach ($rows as $row) {
            $ticket = $this->fromDatabaseRow($row);
            $tickets[] = $ticket;
        }
        return $tickets;
    }

    public function getUpdatedAt(int $id): Carbon
    {
        return new Carbon(
            DB::table('tickets')
            ->where('id', '=', $id)
            ->value('updated_at')
        );
    }

    /**
     * @inheritDoc
     */
    public function create(string $category, string $title, string $content,
                           ?User  $user, ?MuckDbref $character): SupportTicket
    {
        $array = [
            'category' => $category,
            'title' => $title,
            'content' => $content
        ];
        if ($user) $array['from_aid'] = $user->getAid();
        if ($character) $array['from_muck_object_id'] = $this->muckObjects->getMuckObjectIdFor($character);
        $newId = DB::table('tickets')->insertGetId($array);
        return $this->getById($newId);
    }

    /**
     * @inerhitDoc
     */
    public function save(SupportTicket $ticket)
    {
        DB::table('tickets')
            ->where('id', '=', $ticket->id)
            ->update([
                'category' => $ticket->category,
                'title' => $ticket->title,
                'updated_at' => $ticket->updatedAt,
                'status' => $ticket->status,
                'status_at' => $ticket->statusAt,
                'closure_reason' => $ticket->closureReason,
                'closed_at' => $ticket->closedAt,
                'public' => $ticket->isPublic,
                'agent_aid' => $ticket->agentUser?->getAid(),
                'agent_muck_object_id' => $ticket->agentCharacter ? $this->muckObjects->getMuckObjectIdFor($ticket->agentCharacter) : null
            ]);
    }

    /**
     * @inerhitDoc
     */
    public function log(SupportTicket $ticket, string $logType, bool $isPublic, ?User $fromUser, ?MuckDbref $fromMuckDbref, string $content): void
    {
        $values = [
            'ticket_id' => $ticket->id,
            'type' => $logType,
            'staff_only' => !$isPublic,
            'content' => $content
        ];
        if ($fromUser) $values['from_aid'] = $fromUser->getAid();
        if ($fromMuckDbref) $values['from_muck_object_id'] = $this->muckObjects->getMuckObjectIdFor($fromMuckDbref);
        DB::table('ticket_log')->insert($values);
    }

    /**
     * @inheritDoc
     */
    public function getLog(SupportTicket $ticket): array
    {
        $result = [];
        $rows = DB::table('ticket_log')
            ->where('ticket_id', '=', $ticket->id)
            ->orderBy('id')
            ->get();
        foreach ($rows as $row) {
            $result[] = new SupportTicketLog(
                new Carbon($row->created_at),
                $row->type,
                $row->staff_only == 1,
                $row->content,
                $row->from_aid ? User::find($row->from_aid) : null,
                $row->from_muck_object_id ? $this->muckObjects->getByMuckObjectId($row->from_muck_object_id) : null
            );
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getLinks(SupportTicket $ticket): array
    {
        $result = [];
        $rows = DB::table('ticket_links')
            ->where('from_ticket_id', '=', $ticket->id)
            ->orWhere('to_ticket_id', '=', $ticket->id)
            ->get();
        foreach ($rows as $row) {
            $from = $row->from_ticket_id == $ticket->id ? $ticket : $this->getById($row->from_ticket_id);
            $to = $row->to_ticket_id == $ticket->id ? $ticket : $this->getById($row->to_ticket_id);
            $result[] = new SupportTicketLink($from, $to, $row->link_type);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function link(SupportTicket $from, SupportTicket $to, string $linkType): void
    {
        DB::table('ticket_links')
            ->insert([
                'from_ticket_id' => $from->id,
                'to_ticket_id' => $to->id,
                'link_type' => $linkType
            ]);
    }

    /**
     * @inheritDoc
     */
    public function getWatchers(SupportTicket $ticket): array
    {
        $result = [];
        $rows = DB::table('ticket_watchers')
            ->where('ticket_id', '=', $ticket->id)
            ->get();
        foreach ($rows as $row) {
            $result[] = User::find($row->aid);
        }
        return $result;
    }

    public function addWatcher(SupportTicket $ticket, User $user): void
    {
        DB::table('ticket_watchers')
            ->insert([
                'ticket_id' => $ticket->id,
                'aid' => $user->getAid()
            ]);
    }

    public function removeWatcher(SupportTicket $ticket, User $user): void
    {
        DB::table('ticket_watchers')
            ->where('ticket_id', '=', $ticket->id)
            ->where('aid', '=', $user->getAid())
            ->delete();
    }
}
