<?php

namespace App\Avatar;

use Exception;
use Illuminate\Support\Facades\Log;
use Imagick;

class AvatarService
{

    private string $dollFolder = 'app/avatar/doll/';

    private int $width = 384;
    private int $height = 640;
    private int $gradientSize = 2048; // Aiming for 10-bit, since that's growing in usage

    private array $bodyParts = ['torso', 'head', 'arms', 'legs', 'ass', 'groin'];

    /**
     * Array of [subpart, part] in drawing order
     * @var array<array>
     */
    private array $subParts;

    private array $dollImageCache = [];
    private array $dollLayerInformationCache = [];

    /**
     * @var array<string, AvatarDrawingPlan>
     */
    private array $avatarDrawingPlanCache = [];

    public function __construct(
        private AvatarProvider $provider
    )
    {
        $this->subParts = [
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
    }

    public function avatarWidth(): int
    {
        return $this->width;
    }

    public function avatarHeight(): int
    {
        return $this->height;
    }


    /**
     * @return string[]
     */
    public function getDollNames(): array
    {
        $dolls = [];
        $files = glob(storage_path($this->dollFolder . '*.psd'));
        foreach ($files as $file) {
            $fileName = basename($file);
            $dolls[] = substr($fileName, 0, -4); // Remove file extension
        }
        return $dolls;
    }

    public function getDoll($dollName): Imagick
    {
        if (array_key_exists($dollName, $this->dollImageCache)) return $this->dollImageCache[$dollName];
        $filePath = storage_path($this->dollFolder . $dollName . '.psd');
        Log::debug("Loading doll file from " . $filePath);
        if (!file_exists($filePath)) throw new Exception("Specified doll file not found");
        $doll = new Imagick($filePath);
        $this->dollImageCache[$dollName] = $doll;
        return $doll;
    }

    public function getBaseCodeForDoll(string $dollName) : string
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
     * Calculates a breakdown of the layers in a doll
     * Returns an array of subpart => array[layerIndex, colorChannel]..]
     * @param string $dollName
     * @return array
     * @throws \ImagickException
     */
    public function getDollLayerInformation(string $dollName): array
    {
        if (array_key_exists($dollName, $this->dollLayerInformationCache)) return $this->dollLayerInformationCache[$dollName];
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

        $this->dollLayerInformationCache[$dollName] = $array;
        return $array;
    }

    /**
     * Return an array, in order of drawing, of:
     * [ dollName, doll, subPart, layers ]
     * Layers is an array of [ colorChannel, layerIndex ]
     * @param AvatarInstance $avatar
     * @return AvatarDrawingPlan
     * @throws \ImagickException
     */
    public function getDrawingPlanForAvatarInstance(AvatarInstance $avatar): AvatarDrawingPlan
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
        // Get a collection of the required dolls and a collection of layer info.
        // Since these are cached we don't need to go out of our way to avoid duplicates
        $parts = [];
        foreach ($this->bodyParts as $bodyPart) {
            if ($avatar->mode == AvatarInstance::MODE_HEAD_ONLY && $bodyPart != 'head') continue;
            $parts[$bodyPart] = [
                'dollName' => $dollNames[$bodyPart],
                'doll' => $this->getDoll($dollNames[$bodyPart]),
                'layerInfo' => $this->getDollLayerInformation($dollNames[$bodyPart])
            ];
        }

        // Build drawing plan based off of the subpart array since such is in drawing order
        foreach ($this->subParts as $partInfo) {
            [$subPart, $part] = $partInfo;
            if (!array_key_exists($part, $parts)) continue;
            $layerInfo = $parts[$part]['layerInfo'];
            if (array_key_exists($subPart, $layerInfo)) {
                $drawingSteps[] = new AvatarDrawingStep(
                    $parts[$part]['dollName'],
                    $parts[$part]['doll'],
                    $part, $subPart,
                    $layerInfo[$subPart]
                );
            }
        }

        // Grab the default colors for each layer. Order isn't important at this stage
        $colors = [
            (AvatarInstance::COLOR_PRIMARY) =>
                $this->getGradientImageFromName($avatar->colors[AvatarInstance::COLOR_PRIMARY] ?? 'Earth'),
            (AvatarInstance::COLOR_SECONDARY) =>
                $this->getGradientImageFromName($avatar->colors[AvatarInstance::COLOR_SECONDARY] ?? 'Gold'),
            (AvatarInstance::COLOR_NAUGHTY_BITS) =>
                $this->getGradientImageFromName($avatar->colors[AvatarInstance::COLOR_NAUGHTY_BITS] ?? 'Hot Pink'),
            (AvatarInstance::COLOR_HAIR) =>
                $this->getGradientImageFromName($avatar->colors[AvatarInstance::COLOR_HAIR] ?? 'Blonde'),
            (AvatarInstance::COLOR_EYES) =>
                $this->getGradientImageFromName($avatar->colors[AvatarInstance::COLOR_EYES] ?? 'Sky Blue'),

        ];
        $drawingPlan = new AvatarDrawingPlan($drawingSteps, $colors);

        $this->avatarDrawingPlanCache[$avatar->code] = $drawingPlan;
        return $drawingPlan;
    }

    // Internal function to handle shared parts of rendering an avatar
    private function renderAvatarFromPlan(AvatarDrawingPlan $drawingPlan): Imagick
    {
        //Create a blank canvas
        $image = new Imagick();
        $image->newImage($this->width, $this->height, 'transparent');
        $image->setImageFormat("png");

        foreach ($drawingPlan->steps as $step) {
            //Prepare gradients
            $gradients = [ // Order is important here as the layers refer to them by index
                $drawingPlan->colors[AvatarInstance::COLOR_PRIMARY],
                $drawingPlan->colors[AvatarInstance::COLOR_SECONDARY],
                $drawingPlan->colors[AvatarInstance::COLOR_HAIR],
                $drawingPlan->colors[AvatarInstance::COLOR_NAUGHTY_BITS],
                $drawingPlan->colors[AvatarInstance::COLOR_EYES]
            ];

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
                $subPart->clutImage($gradients[$colorChannel], Imagick::CHANNEL_DEFAULT);

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
            $fromPixel = (int)($fromStep[0] * $this->gradientSize / 255.0);
            $toPixel = (int)($toStep[0] * $this->gradientSize  / 255.0);
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

    public function renderGradientAvatarPreview(AvatarGradient $gradient) : Imagick
    {
        $gradientImage = $this->renderGradientImage($gradient);
        $avatar = new AvatarInstance('FS_Husky', mode: AvatarInstance::MODE_HEAD_ONLY);
        $drawingPlan = $this->getDrawingPlanForAvatarInstance($avatar);
        $drawingPlan->colors[AvatarInstance::COLOR_PRIMARY] = $gradientImage;
        $drawingPlan->colors[AvatarInstance::COLOR_SECONDARY] = $gradientImage;
        $drawingPlan->colors[AvatarInstance::COLOR_HAIR] = $gradientImage;
        return $this->renderAvatarFromPlan($drawingPlan);
    }

    public function getGradientImageFromName(string $name) : Imagick
    {
        return $this->renderGradientImage($this->getGradient($name));
    }
    #endregion Gradients
}