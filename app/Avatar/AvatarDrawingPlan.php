<?php

namespace App\Avatar;

use Imagick;

/**
 * This is the heavier version of AvatarInstance where resources are linked and calculations on how to draw it are done
 */
class AvatarDrawingPlan
{
    public function __construct(
        /**
         * @var AvatarDrawingPlan[]
         */
        public array $steps,

        /**
         * @var array<string, Imagick> Array of defaults for specific color channels
         */
        public array $colors = []
    )
    {

    }
}
