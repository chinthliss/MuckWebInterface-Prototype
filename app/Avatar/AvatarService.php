<?php

namespace App\Avatar;

use Exception;
use Illuminate\Support\Facades\Log;
use Imagick;

class AvatarService
{

    const DOLL_FILE_LOCATION = 'app/avatar/doll/';

    const DOLL_WIDTH = 384;
    const DOLL_HEIGHT = 640;
    const GRADIENT_SIZE = 2048; // Aiming for 10-bit, since that's growing in usage

    const MODE_HEAD_ONLY = 'head_only';

    const COLOR_PRIMARY = "skin1";
    const COLOR_SECONDARY = "skin2";
    const COLOR_NAUGHTY_BITS = 'skin3';
    const COLOR_HAIR = 'hair';
    const COLOR_EYES = 'eyes';

    const COLOR_INDEX_VALUES = [
        self::COLOR_PRIMARY => 0,
        self::COLOR_SECONDARY => 1,
        self::COLOR_NAUGHTY_BITS => 3,
        self::COLOR_HAIR => 2,
        self::COLOR_EYES => 4
    ];

    /**
     * @var string[] AVATARDOLL_BODYPARTS
     */
    const AVATARDOLL_BODYPARTS = ['torso', 'head', 'arms', 'legs', 'ass', 'groin'];

    /**
     * @var array<array> AVATARDOLL_SUBPARTS Array of [subpart, bodypart] in drawing order
     */
    const AVATARDOLL_SUBPARTS = [
        ['leg2', 'legs'],
        ['arm2', 'arms'],
        ['ass', 'ass'],
        ['torso', 'torso'],
        ['breasts', 'torso'],
        ['sheath', 'groin'],
        ['leg1', 'legs'],
        ['nipples', 'torso'],
        ['penis', 'groin'],
        ['arm1', 'arms'],
        ['ear1', 'head'],
        ['hair1', 'head'],
        ['head', 'head'],
        ['expr', 'head'],
        ['hair2', 'head'],
        ['ear2', 'head']
    ];

    //Image files
    private array $dollImageCache = [];

    //Drawing order, needs to be done through Imagick to ensure the layer index values match.
    private array $dollLayerDrawingOrderCache = [];

    //Loaded from PSD file requiring custom loader due to not being part of Imagick
    private array $dollDefaultGradientsCache = [];

    /**
     * @var array<string, AvatarDrawingStep[]>
     */
    private array $avatarDrawingPlanCache = [];

    public function __construct(
        private AvatarProvider $provider
    )
    {
    }

    /**
     * @return string[]
     */
    public function getDollNames(): array
    {
        $dolls = [];
        $files = glob(storage_path(self::DOLL_FILE_LOCATION . '*.psd'));
        foreach ($files as $file) {
            $fileName = basename($file);
            $dolls[] = substr($fileName, 0, -4); // Remove file extension
        }
        return $dolls;
    }

    public function getDollFileName(string $dollName): string
    {
        return storage_path(self::DOLL_FILE_LOCATION . $dollName . '.psd');
    }

    public function getDoll($dollName): Imagick
    {
        if (array_key_exists($dollName, $this->dollImageCache)) return $this->dollImageCache[$dollName];
        $filePath = $this->getDollFileName($dollName);
        Log::debug("getDoll loading PSD file from " . $filePath);
        if (!file_exists($filePath)) throw new Exception("Specified doll file not found");
        $doll = new Imagick($filePath);
        $this->dollImageCache[$dollName] = $doll;
        return $doll;
    }

    public function getBaseCodeForDoll(string $dollName): string
    {
        $avatar = new AvatarInstance($dollName);
        return $avatar->code;
    }

    public function getDollThumbnail($dollName): Imagick
    {
        $image = $this->getDoll($dollName);

        // Image 0 is a cached flattened copy that might have things we don't want, such as a background
        // However it holds the extent of the image, so we also need to add a transparent image of equal size
        $image->setIteratorIndex(0);
        $imageDimensions = $image->getImageGeometry();
        $image->removeImage();
        $image->newImage($imageDimensions['width'], $imageDimensions['height'], 'transparent');

        //Iterating backwards since we're potentially removing layers
        for ($i = $image->getNumberImages() - 1; $i >= 0; $i--) {
            $image->setIteratorIndex($i);
            $label = strtolower($image->getImageProperty('label'));
            // Log::debug("File {$dollName}, {$i} label = " . $label);

            // Some files have a separate background layer we don't want
            if (str_starts_with($label, 'background')
                || str_starts_with($label, 'layer')
                || str_starts_with($label, 'bg')) $image->removeImage();
            else
                $image->setImageBackgroundColor('transparent');
        }

        // Flatten PSD file and create the actual thumbnail
        $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_COALESCE);
        $image->thumbnailImage(100, 0);
        $image->setImageFormat('png');
        return $image;
    }

    /**
     * Gets the default gradients for a doll (from the PSD file or cache)
     * @param string $dollName
     * @return array Array of 5 gradients with the indexes matching the ones referenced in the PSD
     * @throws Exception
     */
    public function getDollDefaultGradientInformation(string $dollName): array
    {
        if (array_key_exists($dollName, $this->dollDefaultGradientsCache)) return $this->dollDefaultGradientsCache[$dollName];

        $filePath = $this->getDollFileName($dollName);
        Log::debug("getDollLayerInformation loading PSD file from " . $filePath);
        if (!file_exists($filePath)) throw new Exception("Specified doll file not found");

        $raw = AvatarDollPsdReader::loadFromFile($filePath);

        //Process the result into the format we want for this
        $result = [null, null, null, null, null];

        foreach ($raw['gradients'] as $unprocessedGradient) {
            $index = null; // Whether we found a place for this one to go
            switch ($unprocessedGradient['layer']) {
                case 'Fur 1':
                    $index = 0;
                    break;
                case 'Fur 2':
                    $index = 1;
                    break;
                case 'Hair':
                    $index = 2;
                    break;
                case 'Bare Skin':
                    $index = 3;
                    break;
                case 'Eyes':
                    $index = 4;
                    break;
                default:
                    break;
            }
            if ($index !== null) {
                $steps = [];
                foreach ($unprocessedGradient['colorStops'] as $stop) {
                    $steps[] = [
                        $stop['location'],
                        $stop['r'],
                        $stop['g'],
                        $stop['b']
                    ];
                }
                $gradient = new AvatarGradient(
                    "_" . $dollName . "_" . $index,
                    "Internally generated gradient",
                    $steps,
                    true
                );
                $result[$index] = $gradient;
            }
        }

        $this->dollDefaultGradientsCache[$dollName] = $result;
        return $result;
    }

    /**
     * Calculates a breakdown of the layers in a doll and the order they need to be drawn in
     * Returns an array of subpart => array[layerIndex, colorChannel]..]
     * @param string $dollName
     * @return array
     * @throws \ImagickException
     */
    public function getDollLayerDrawingOrder(string $dollName): array
    {
        if (array_key_exists($dollName, $this->dollLayerDrawingOrderCache)) return $this->dollLayerDrawingOrderCache[$dollName];
        Log::debug("Calculating doll layer information for " . $dollName);
        $array = [];
        $image = $this->getDoll($dollName);

        for ($i = 1; $i < $image->getNumberImages(); $i++) {
            $image->setIteratorIndex($i);
            $layerName = strtolower($image->getImageProperty('label'));
            // If it's a managed layer it's in the form [subpart]_clr[color channel]_[order], e.g. arm_clr1_2
            // Since we're loading the layers in drawing order, we don't actually use [order] anymore.
            if ($start = strpos($layerName, '_clr')) {
                $subPart = substr($layerName, 0, $start);
                $details = substr($layerName, $start + 4);
                [$channel, $order] = explode('_', $details, 2);
                if (!array_key_exists($subPart, $array)) $array[$subPart] = [];
                $array[$subPart][] = [
                    'layerIndex' => $i,
                    'colorChannel' => (int)$channel
                ];
            }
        }

        $this->dollLayerDrawingOrderCache[$dollName] = $array;
        return $array;
    }

    /**
     * Return an array, in order of drawing, of:
     * [ dollName, doll, subPart, layers ]
     * Layers is an array of [ colorChannel, layerIndex ]
     * @param AvatarInstance $avatar
     * @return AvatarDrawingStep[]
     * @throws \ImagickException
     */
    public function getDrawingPlanForAvatarInstance(AvatarInstance $avatar): array
    {
        if (array_key_exists($avatar->code, $this->avatarDrawingPlanCache)) return $this->avatarDrawingPlanCache[$avatar->code];
        Log::debug("Calculating drawing plan for " . $avatar->code);

        $drawingSteps = [];

        // Get a collection of which doll to use for which part
        $dollNames = [
            'torso' => $avatar->torso,
            'head' => $avatar->head ?? $avatar->torso,
            'arms' => $avatar->arms ?? $avatar->torso,
            'legs' => $avatar->legs ?? $avatar->torso,
            'groin' => $avatar->groin ?? $avatar->torso,
            'ass' => $avatar->ass ?? $avatar->torso
        ];

        // Get a collection of the overridden gradients. Order is important since they're referred by index
        $colorOverrides = [null, null, null, null, null];
        foreach (self::COLOR_INDEX_VALUES as $color => $index) {
            if (array_key_exists($color, $avatar->colors)) $colorOverrides[$index] = $this->getGradientImageFromName($avatar->colors[$color]);
        }

        // Get a collection of the required dolls and a collection of layer info.
        // Since these are cached we don't need to go out of our way to avoid duplicates
        $parts = [];
        foreach (self::AVATARDOLL_BODYPARTS as $bodyPart) {
            if ($avatar->mode == self::MODE_HEAD_ONLY && $bodyPart != 'head') continue;
            $dollName = $dollNames[$bodyPart];
            $parts[$bodyPart] = [
                'dollName' => $dollName,
                'doll' => $this->getDoll($dollName),
                'layerInfo' => $this->getDollLayerDrawingOrder($dollNames[$bodyPart])
            ];
        }

        // Build drawing plan based off of the subpart array since such is in drawing order
        foreach (self::AVATARDOLL_SUBPARTS as $partInfo) {
            [$subPart, $part] = $partInfo;
            if (!array_key_exists($part, $parts)) continue;
            $layerInfo = $parts[$part]['layerInfo'];
            if (array_key_exists($subPart, $layerInfo)) {
                $defaults = $this->getDollDefaultGradientInformation($parts[$part]['dollName']);
                $colors = [];
                foreach (self::COLOR_INDEX_VALUES as $color => $index) {
                    $colors[$index] = $colorOverrides[$index] ?: $this->renderGradientImage($defaults[$index]);
                }
                $drawingSteps[] = new AvatarDrawingStep(
                    $parts[$part]['dollName'], $parts[$part]['doll'],
                    $part, $subPart,
                    $layerInfo[$subPart], $colors
                );
            }
        }


        $this->avatarDrawingPlanCache[$avatar->code] = $drawingSteps;
        return $drawingSteps;
    }

    /**
     * Internal function to handle shared parts of rendering an avatar
     * @param AvatarDrawingStep[] $drawingPlan
     * @return Imagick
     * @throws \ImagickException
     */
    private function renderAvatarFromPlan(array $drawingPlan): Imagick
    {
        //Create a blank canvas
        $image = new Imagick();
        $image->newImage(self::DOLL_WIDTH, self::DOLL_HEIGHT, 'transparent');
        $image->setImageFormat("png");

        foreach ($drawingPlan as $step) {

            /** @var Imagick $doll */
            $doll = $step->doll;
            foreach ($step->layers as $layer) {
                $colorChannel = $layer['colorChannel'] - 1;
                $colorChannel = max(0, $colorChannel); // Couple of avatars have 0 instead of 1
                $doll->setIteratorIndex($layer['layerIndex']);
                $extents = $doll->getImagePage(); // Returns width, height, x and y (offsets) for this layer

                // Take a copy of that relevant layer and use the gradient as a color lookup table (clut) on it
                $subPart = new Imagick();
                $subPart->newImage($extents['width'], $extents['height'], 'transparent');
                $subPart->compositeImage($doll, Imagick::COMPOSITE_OVER, 0, 0);
                $subPart->clutImage($step->colorChannels[$colorChannel], Imagick::CHANNEL_DEFAULT);

                // Copy the subPage onto our final image, using its original offsets
                $image->compositeImage($subPart, Imagick::COMPOSITE_OVER,
                    $extents['x'], $extents['y']);
            }
        }
        return $image;
    }

    public function renderAvatarInstance(AvatarInstance $avatar): Imagick
    {
        return $this->renderAvatarFromPlan($this->getDrawingPlanForAvatarInstance($avatar));
    }

    #region Gradients

    /**
     * @return AvatarGradient[]
     */
    public function getGradients(): array
    {
        return $this->provider->getGradients();
    }

    public function getGradient(string $name): ?AvatarGradient
    {
        return $this->provider->getGradient($name);
    }

    public function renderGradientImage(AvatarGradient $gradient, ?bool $horizontal = false): Imagick
    {
        Log::debug("Rendering Image for gradient {$gradient->name}");

        //Holding image
        $image = new Imagick();

        $stepCount = count($gradient->steps); // Just for readability

        //Starting from 1 because we want to render from the previous step to this one
        for ($i = 1; $i < $stepCount; $i++) {
            $fromStep = $gradient->steps[$i - 1];
            $toStep = $gradient->steps[$i];
            //Step values and colors are in the range 0..255
            $fromPixel = (int)($fromStep[0] * self::GRADIENT_SIZE / 255.0);
            $toPixel = (int)($toStep[0] *self::GRADIENT_SIZE / 255.0);
            if ($toPixel > $fromPixel) { // Only render steps that are more than a pixel
                $fromColor = "rgb($fromStep[1], $fromStep[2], $fromStep[3])";
                $toColor = "rgb($toStep[1], $toStep[2], $toStep[3])";
                $image->newPseudoImage(1, $toPixel - $fromPixel, "gradient:$fromColor-$toColor");
                $image->setImagePage(1, $toPixel - $fromPixel, 0, $fromPixel);
            }
        }
        $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_COALESCE);
        $image->setImageFormat('png');
        if ($horizontal) $image->rotateImage('transparent', -90);
        return $image;
    }

    public function renderGradientAvatarPreview(AvatarGradient $gradient): Imagick
    {
        $gradientImage = $this->renderGradientImage($gradient);
        $avatar = new AvatarInstance('FS_Husky', mode: self::MODE_HEAD_ONLY);
        $drawingPlan = $this->getDrawingPlanForAvatarInstance($avatar);
        // Now need to step through the plan and overwrite colors to our new temporary one
        foreach($drawingPlan as $step) {
            $step->colorChannels[self::COLOR_INDEX_VALUES[self::COLOR_PRIMARY]] = $gradientImage;
            $step->colorChannels[self::COLOR_INDEX_VALUES[self::COLOR_SECONDARY]] = $gradientImage;
            $step->colorChannels[self::COLOR_INDEX_VALUES[self::COLOR_HAIR]] = $gradientImage;
        }
        return $this->renderAvatarFromPlan($drawingPlan);
    }

    public function getGradientImageFromName(string $name): Imagick
    {
        return $this->renderGradientImage($this->getGradient($name));
    }
    #endregion Gradients
}
