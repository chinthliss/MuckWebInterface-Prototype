<?php


namespace App\Payment;

use Illuminate\Support\Carbon;

class PatreonMember
{
    /**
     * @var PatreonUser
     */
    public $patron;

    /**
     * @var integer
     */
    public $campaignId;

    /**
     * @var integer
     */
    public $currentlyEntitledAmountCents = 0;

    /**
     * @var integer
     */
    public $rewardedCents = 0;

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
     * @var bool Whether to save to the DB
     */
    public $updated = false;

    public function __construct(PatreonUser $patreonUser, $campaignId)
    {
        $this->patron = $patreonUser;
        $this->campaignId = $campaignId;
        $patreonUser->memberships[$campaignId] = $this;
    }

    public function toDatabase()
    {
        return [
            'campaign_id' => $this->campaignId,
            'patron_id' => $this->patron->patronId,
            'currently_entitled_amount_cents' => $this->currentlyEntitledAmountCents,
            'is_follower' => $this->isFollower,
            'last_charge_status' => $this->lastChargeStatus,
            'last_charge_date' => $this->lastChargeDate,
            'lifetime_support_cents' => $this->lifetimeSupportCents,
            'patron_status' => $this->patronStatus,
            'pledge_relationship_start' => $this->pledgeRelationshipStart,
            'updated_at' => $this->updatedAt
        ];
    }

    public static function fromDatabase($row, PatreonUser $patreonUser) : PatreonMember
    {
        $member = new PatreonMember($patreonUser, $row->campaign_id);
        $member->currentlyEntitledAmountCents = $row->currently_entitled_amount_cents;
        $member->isFollower = $row->is_follower;
        $member->lastChargeStatus = $row->last_charge_status;
        $member->lastChargeDate = $row->last_charge_date;
        $member->lifetimeSupportCents = $row->lifetime_support_cents;
        if (property_exists($row, 'rewarded_usd') && $row->rewarded_usd)
            $member->rewardedCents = $row->rewarded_usd * 100;
        $member->patronStatus = $row->patron_status;
        $member->pledgeRelationshipStart = $row->pledge_relationship_start;
        $member->updatedAt = $row->updated_at;
        return $member;
    }
}
