<?php

namespace App\Avatar;

use App\User;
use Exception;

/**
 * Utility class to hold a gradient.
 */
class AvatarGradient
{
    /**
     * @throws Exception
     */
    public function __construct(
        public string $name,
        public string $desc,

        /**
         * @var array Each step is an array of [when, red, green, blue] with values between 0 and 255
         */
        public array  $steps,
        public bool   $free,
        public ?User  $owner
    )
    {
        if (!count($this->steps)) throw new Exception("A gradient requires at least one step.");

        // Ensure first step starts at 0, if not create a copy of the exising first step starting at such
        $first = $this->steps[0];
        if ($first[0] !== 0) array_unshift($this->steps, [0, $first[1], $first[2], $first[3]]);

        // Ensure last step ends at 255, if not make a copy of the existing last step at such
        $last = $this->steps[count($this->steps) - 1];
        if ($last[0] !== 255) $this->steps[] = [255, $last[1], $last[2], $last[3]];
    }

    /**
     * @throws Exception
     */
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
