<?php

namespace App\Avatar;

use Imagick;

/**
 * Utility class to hold the assets required to render an Avatar.
 * A large part of such are rendered on demand so should be called from the service
 */
class AvatarDoll
{
    /**
     * @var Imagick[] Indexed based upon drawing calls.
     */
    public array $renderedGradients = [];

    /**
     * @var array<string, array> $drawingInformation Array of [subpart => [layerIndex, colorChannel]]
     */
    public array $drawingInformation = [];

    /**
     * @var AvatarGradient[] Indexed based upon drawing calls
     */
    public array $defaultGradients = [];

    /**
     * @param string $name
     * @param Imagick $image
     */
    public function __construct(
        public string  $name,
        public Imagick $image
    )
    {
        //Ensure there's a null in place for each expected index in the renderedGradients list
        foreach (AvatarService::COLOR_INDEX_VALUES as $value) {
            $this->renderedGradients[] = null;
        }
    }
}
