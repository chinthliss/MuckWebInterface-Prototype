<?php

namespace App\Avatar;

use App\User;
use Exception;
use Illuminate\Support\Carbon;

/**
 * Utility class to hold a gradient.
 */
class AvatarGradient
{
    /**
     * @throws Exception
     */
    public function __construct(
        public string  $name,
        public string  $desc,

        /**
         * @var array Each step is an array of [when, red, green, blue] with values between 0 and 255
         */
        public array   $steps,
        public bool    $free,
        public ?Carbon $created_at = null,
        public ?User   $owner = null
    )
    {
        if (!$this->created_at) $this->created_at = Carbon::now();

        if (!count($this->steps)) throw new Exception("A gradient requires at least one step.");

        //Validation, do by stepping through so we can report errors with an index.
        for ($i = 0; $i < count($this->steps); $i++) {
            $step = $this->steps[$i];
            if (count($step) != 4)
                throw new Exception("Gradient step $i isn't in the form [When, R, G, B]");

            if ($step[0] < 0 || $step[0] > 255)
                throw new Exception("When value of Gradient step $i isn't a integer between 0 and 255.");

            if ($step[1] < 0 || $step[1] > 255)
                throw new Exception("Red value of Gradient step $i isn't a integer between 0 and 255.");

            if ($step[2] < 0 || $step[2] > 255)
                throw new Exception("Green value of Gradient step $i isn't a integer between 0 and 255.");

            if ($step[3] < 0 || $step[3] > 255)
                throw new Exception("Blue value of Gradient step $i isn't a integer between 0 and 255.");
        }

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
            $array['created_at'] ?? null,
            $array['owner_aid'] ?? null
        );
    }

}
