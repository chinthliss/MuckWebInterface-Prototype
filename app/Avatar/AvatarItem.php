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
        public string $id,
        public string $name,
        public string $filename,
        public string $type,
        public ?string $requirement,
        public Carbon $createdAt,
        public ?User $owner,
        public ?int $cost,
        public ?int $x = 0,
        public ?int $y = 0,
        public ?int $z = 0,
        public ?float $rotate = 0.0,
        public ?float $scale = 0.0
    )
    {
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'filename' => $this->filename,
            'type' => $this->type,
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z,
            'rotate' => $this->rotate,
            'scale' => $this->scale
        ];
    }

}
