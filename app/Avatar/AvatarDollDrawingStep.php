<?php

namespace App\Avatar;

use Imagick;

/**
 * Single step of an AvatarDrawingPlan
 */
class AvatarDollDrawingStep
{
    public function __construct(
        public string  $dollName,
        public Imagick $doll,
        public string  $part,
        public string  $subPart,

        /**
         * @var array List of [colorChannel, layerIndex]
         */
        public array   $layers,

        /**
         * @var Imagick[] The gradient images to use for coloring
         */
        public array   $colorChannels
    )
    {
    }
}
