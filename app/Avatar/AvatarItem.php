<?php

namespace App\Avatar;

use App\User;
use Illuminate\Support\Carbon;

/**
 * Details for something rendered onto an avatar
 */
class AvatarItem
{
    public function __construct(
        public string $name,
        public string $filename,
        public string $type,
        public ?string $requirement,
        public Carbon $createdAt,
        public ?User $owner,
        public ?int $cost,
        public ?int $x,
        public ?int $y,
        public ?int $rotate,
        public ?int $scale
    )
    {
    }

}
