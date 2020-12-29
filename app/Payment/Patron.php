<?php


namespace App\Payment;

use Illuminate\Support\Carbon;

class Patron
{
    /**
     * @var integer
     */
    public $campaignId;

    /**
     * @var integer
     */
    public $patronId;

    /**
     * @var string
     */
    public $fullName;

    /**
     * @var bool
     */
    public $hidePledges;

    /**
     * @var string
     */
    public $thumbUrl;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $vanity;

    /**
     * @var integer
     */
    public $currentlyEntitledAmountCents;

    /**
     * @var string
     */
    public $email;

    /**
     * @var bool
     */
    public $isFollower;

    /**
     * @var string
     */
    public $lastChargeStatus;

    /**
     * @var Carbon
     */
    public $lastChargeDate;

    /**
     * @var integer
     */
    public $lifetimeSupportCents;

    /**
     * @var string
     */
    public $patronStatus;

    /**
     * @var Carbon
     */
    public $pledgeRelationshipStart;

    /**
     * @var Carbon
     */
    public $updatedAt;

    /**
     * @var bool
     */
    public $updated = false; //Whether we need to upload this record. This isn't actually stored in the DB

    public function __construct($campaignId, $patronId)
    {
        $this->campaign_id = $campaignId;
        $this->patron_id = $patronId;
    }

    public function toDatabase()
    {
        return [
            'campaign_id' => $this->campaignId,
            'patron_id' => $this->patronId,
            'email' => $this->email,
            'full_name' => $this->fullName,
            'vanity' => $this->vanity,
            'hide_pledges' => $this->hidePledges ? 'Y' : 'N',
            'currently_entitled_amount_cents' => $this->currentlyEntitledAmountCents,
            'is_follower' => $this->isFollower ? 'Y' : 'N',
            'last_charge_status' => $this->lastChargeStatus,
            'last_charge_date' => $this->lastChargeDate,
            'lifetime_support_cents' => $this->lifetimeSupportCents,
            'patron_status' => $this->patronStatus,
            'pledge_relationship_start' => $this->pledgeRelationshipStart,
            'url' => $this->url,
            'thumb_url' => $this->thumbUrl,
            'updated_at' => $this->updatedAt
        ];
    }

    public static function fromDatabase($row) : Patron
    {
        $patron = new Patron($row->campaign_id, $row->patron_id);
        $patron->email = $row->email;
        $patron->fullName = $row->full_name;
        $patron->vanity = $row->vanity;
        $patron->hidePledges = $row->hide_pledges == 'Y';
        $patron->currentlyEntitledAmountCents = $row->currently_entitled_amount_cents;
        $patron->isFollower = $row->is_follower == 'Y';
        $patron->lastChargeStatus = $row->last_charge_status;
        $patron->lastChargeDate = $row->last_charge_date;
        $patron->lifetimeSupportCents = $row->lifetime_support_cents;
        $patron->patronStatus = $row->patron_status;
        $patron->pledgeRelationshipStart = $row->pledge_relationship_start;
        $patron->url = $row->url;
        $patron->thumbUrl = $row->thumb_url;
        $patron->updatedAt = $row->updated_at;
        return $patron;
    }
}
