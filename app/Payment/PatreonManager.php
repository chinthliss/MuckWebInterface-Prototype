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
     * Indexed in the form patronId:Patron
     * @var Patron[]|null
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

        $rows = DB::table('patreon')->get();
        foreach ($rows as $row) {
            $patron = Patron::fromDatabase($row);
            $this->patrons[$patron->patronId] = $patron;
        }
    }

    /**
     * Loads presently known patrons from the database or from memory.
     * Does not update from Patreon.
     */
    public function getPatrons()
    {
        $this->loadFromDatabaseIfRequired();
        return $this->patrons;
    }

}
