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
        public int $x = 0,
        public int $y = 0,
        public int $z = 0,
        public int $rotate = 0,
        public float $scale = 1.0
    )
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'x' => $this->x ?? 0,
            'y' => $this->y ?? 0,
            'z' => $this->z ?? 0,
            'rotate' => $this->rotate ?? 0,
            'scale' => $this->scale ?? 1.0
        ];
    }

    public function toCatalogArray(): array
    {
        $array = $this->toArray();
        $array['name'] = $this->name;
        $array['url'] = route('multiplayer.avatar.item', ['id' => $this->id]);
        $array['preview_url'] = route('multiplayer.avatar.itempreview', ['id' => $this->id]);
        $array['cost'] = $this->cost ?? 0;
        return $array;
    }

}
