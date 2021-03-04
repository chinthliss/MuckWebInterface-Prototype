<?php


namespace App\Payment;

use Illuminate\Support\Carbon;

class PatreonUser
{
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
     * @var string
     */
    public $email;

    /**
     * @var Carbon
     */
    public $updatedAt;

    /**
     * @var bool Whether to save to the DB
     */
    public $updated = false;

    /**
     * @var PatreonMember[];
     */
    public $memberships = [];

    public function __construct($patronId)
    {
        $this->patronId = $patronId;
    }

    public function toDatabase()
    {
        return [
            'patron_id' => $this->patronId,
            'email' => $this->email,
            'full_name' => $this->fullName,
            'vanity' => $this->vanity,
            'hide_pledges' => $this->hidePledges,
            'url' => $this->url,
            'thumb_url' => $this->thumbUrl,
            'updated_at' => $this->updatedAt
        ];
    }

    public static function fromDatabase($row): PatreonUser
    {
        $patron = new PatreonUser($row->patron_id);
        $patron->email = $row->email;
        $patron->fullName = $row->full_name;
        $patron->vanity = $row->vanity;
        $patron->hidePledges = $row->hide_pledges;
        $patron->url = $row->url;
        $patron->thumbUrl = $row->thumb_url;
        $patron->updatedAt = $row->updated_at;
        return $patron;
    }

    public function toAdminArray(): array
    {
        $totalSupportCents = 0;
        $totalRewardedCents = 0;
        foreach ($this->memberships as $membership) {
            $totalSupportCents += $membership->lifetimeSupportCents;
            $totalRewardedCents += $membership->rewardedCents;
        }
        return [
            'patronId' => $this->patronId,
            'name' => $this->fullName . ( $this->vanity ? ' (' . $this->vanity . ')' : ''),
            'url' => $this->url,
            'thumbUrl' => $this->thumbUrl,
            'updatedAt' => $this->updatedAt,
            'totalSupportUsd' => round($totalSupportCents / 100.0, 2),
            'totalRewardedUsd' => round($totalRewardedCents / 100.0, 2)
        ];
    }
}
