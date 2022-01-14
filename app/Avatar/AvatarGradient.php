<?php

namespace App\Avatar;

use App\User;

/**
 * Utility class to hold a gradient.
 */
class AvatarGradient
{
    public function __construct(
        public string $name,
        public string $desc,
        public array  $steps,
        public bool   $free,
        public ?User  $owner
    )
    {
    }

    public static function fromArray(array $array): AvatarGradient
    {
        return new AvatarGradient(
            $array['name'],
            $array['desc'],
            $array['steps'],
            $array['free'] ?? false,
            $array['owner_aid'] ?? null
        );
    }

}
