<?php


namespace App\Payment;


use Illuminate\Support\Facades\DB;

class PatreonManager
{
    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string[]
     */
    private $campaigns;

    /**
     * @var string
     */
    private $creatorAccessToken;

    /**
     * @var string
     */
    private $creationRefreshToken;

    /**
     * Loaded on demand
     * Indexed in the form [patronId:PatreonPatron]
     * @var PatreonUser[]|null
     */
    private $patrons = null;

    public function __construct(string $clientId, string $clientSecret,
                                string $creatorAccessToken, string $creatorRefreshToken, string $campaigns)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->creatorAccessToken = $creatorAccessToken;
        $this->creationRefreshToken = $creatorRefreshToken;
        $this->campaigns = explode(',', $campaigns);
    }

    private function loadFromDatabaseIfRequired()
    {
        if ($this->patrons) return;

        $this->patrons = [];
        $rows = DB::table('patreon_users')->get();
        foreach ($rows as $row) {
            $patron = PatreonUser::fromDatabase($row);
            $this->patrons[$patron->patronId] = $patron;
        }

        $rows = DB::table('patreon_members')->get();
        foreach ($rows as $row) {
            $patreonUser = $this->patrons[$row->patron_id];
            $member = PatreonMember::fromDatabase($row, $patreonUser);
            $patreonUser->memberships[$member->campaignId] = $member;
        }

    }

    /**
     * Loads presently known pledges from the database or from memory.
     * Does not update from Patreon.
     */
    public function getPatrons()
    {
        $this->loadFromDatabaseIfRequired();
        return $this->patrons;
    }

    /**
     * Loads historic way of saving claims - returned in the form [patronId:[CampaignId:Amount]]
     */
    public function getLegacyClaims(): array
    {
        $results = [];
        $rows = DB::table('patreon_claims')->get();
        foreach ($rows as $row) {
            if (!array_key_exists($row->patron_id, $results)) $results[$row->patron_id] = [];
            $results[$row->patron_id][$row->campaign_id] = $row->claimed_cents;
        }
        return $results;
    }

}
