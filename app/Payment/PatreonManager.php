<?php


namespace App\Payment;


use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Patreon\API;

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

    private function updateOrCreatePatronFromArray($campaignId, $patronId, $data)
    {
        $patron = $this->getPatron($patronId);

        if (!$patron) {
            $patron = new PatreonUser($patronId);
            $this->patrons[$patronId] = $patron;
            $patron->updated = true;
        }

        $membership = null;
        if (array_key_exists($campaignId, $patron->memberships)) {
            $membership = $patron->memberships[$campaignId];
        } else {
            $membership = new PatreonMember($patron, $campaignId);
            $membership->updated = true;
        }

        // Fix datetimes - only two
        if (array_key_exists('last_charge_date', $data) && $data['last_charge_date'])
            $data['last_charge_date'] = new Carbon($data['last_charge_date']);
        if (array_key_exists('pledge_relationship_start', $data) && $data['pledge_relationship_start'])
            $data['pledge_relationship_start'] = new Carbon($data['pledge_relationship_start']);


        // Potential Patron values
        // NOTE: As of writing, email comes in under the membership instead of on the patron
        $fieldTranslation = [
            'email' => 'email',
            'full_name' => 'fullName',
            'vanity' => 'vanity',
            'hide_pledges' => 'hidePledges',
            'url' => 'url',
            'thumb_url' => 'thumbUrl'
        ];
        foreach($fieldTranslation as $patreonKey => $ourKey) {
            if (array_key_exists($patreonKey, $data) && $data[$patreonKey] != $patron->$ourKey) {
                //echo "Patron Change {$data[$patreonKey]} vs {$patron->$ourKey} \r\n";
                $patron->$ourKey = $data[$patreonKey];
                $patron->updated = true;
            }
        }

        // Potential Membership values
        $fieldTranslation = [
            'currently_entitled_amount_cents' => 'currentlyEntitledAmountCents',
            'is_follower' => 'isFollower',
            'last_charge_status' => 'lastChargeStatus',
            'last_charge_date' => 'lastChargeDate',
            'lifetime_support_cents' => 'lifetimeSupportCents',
            'patron_status' => 'patronStatus',
            'pledge_relationship_start' => 'pledgeRelationshipStart'
        ];
        foreach($fieldTranslation as $patreonKey => $ourKey) {
            if (array_key_exists($patreonKey, $data) && $data[$patreonKey] != $membership->$ourKey) {
                //echo "Membership Change {$data[$patreonKey]} vs {$membership->$ourKey} \r\n";
                $membership->$ourKey = $data[$patreonKey];
                $membership->updated = true;
                $patron->updated = true;
            }
        }
    }

    public function updateFromPatreon()
    {
        $this->loadFromDatabaseIfRequired();

        $apiClient = new API($this->creatorAccessToken);

        foreach ($this->campaigns as $campaignId) {
            // API has a function for this called fetch_page_of_members_from_campaign but it doesn't allow scoping.
            $parameters = http_build_query([
                "include" => "user", //Took out currently_entitled_tier
                "fields[member]" => "is_follower,last_charge_date,last_charge_status"
                    . ",lifetime_support_cents,currently_entitled_amount_cents,patron_status,pledge_relationship_start,email",
                "fields[user]" => "email,is_email_verified,thumb_url,hide_pledges,url,vanity,full_name",
                "page[count]" => "100"
            ]);
            $url = "campaigns/{$campaignId}/members?{$parameters}";
            while ($url) {
                $response = $apiClient->get_data($url);
                //Go through 'included' which should be the user list
                foreach ($response["included"] as $patron_details) {
                    if ($patron_details["type"] == "user") {
                        $patronId = $patron_details["id"];
                        $this->updateOrCreatePatronFromArray($campaignId, $patronId, $patron_details["attributes"]);
                    } else {
                        Log::warning("Non-user object in 'included' collection. Type=" . $patron_details["type"]);
                    }
                }

                //Go through 'data' which is actually pledges, which we'll now flatten into users.
                foreach ($response["data"] as $pledge_details) {
                    if ($pledge_details["type"] == "member") {
                        $patronId = $pledge_details["relationships"]["user"]["data"]["id"];
                        $this->updateOrCreatePatronFromArray($campaignId, $patronId, $pledge_details["attributes"]);
                    } else {
                        Log::warning("Non-member object in 'data' collection. Type=" . $pledge_details["type"]);
                    }
                }


                if (isset($response["links"]["next"])) {
                    $url = str_replace($apiClient->api_endpoint, '', $response["links"]["next"]);
                } else {
                    $url = null;
                }
            }
        }
        // Look for updated entries
        foreach ($this->patrons as $patron) {
            if ($patron->updated) {
                Log::debug("Patreon updating/creating " . $patron->patronId);
                $this->savePatron($patron);
            }
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

    public function getPatron($patronId)
    {
        $this->loadFromDatabaseIfRequired();
        if (array_key_exists($patronId, $this->patrons)) return $this->patrons[$patronId];
        return null;
    }

    public function savePatron(PatreonUser $patron)
    {
        $patron->updatedAt = Carbon::now();
        DB::table('patreon_users')->updateOrInsert(
            ['patron_id' => $patron->patronId],
            $patron->toDatabase()
        );
        $patron->updated = false;

        foreach($patron->memberships as $membership) {
            if ($membership->updated) {
                $membership->updatedAt = Carbon::now();
                DB::table('patreon_members')->updateOrInsert(
                    ['patron_id' => $patron->patronId, 'campaign_id' => $membership->campaignId],
                    $membership->toDatabase()
                );
                $membership->updated = false;
            }
        }
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
