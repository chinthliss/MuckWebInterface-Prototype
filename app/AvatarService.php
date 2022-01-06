<?php

namespace App;

use Exception;
use Illuminate\Support\Facades\Log;
use Imagick;
use PhpParser\Node\Expr\Array_;

class AvatarService
{
    private string $dollFolder = 'app/avatar/doll/';

    private int $width = 384;
    private int $height = 640;

    private array $bodyParts = ['torso', 'head', 'arms', 'legs', 'ass', 'groin'];

    /**
     * Array of [subpart, part] in drawing order
     * @var array<array>
     */
    private array $subParts;

    private array $dollImageCache = [];
    private array $dollLayerInformationCache = [];
    private array $avatarDrawingStepsCache = [];

    public function __construct()
    {
        $this->subParts = [
            ['leg2', 'legs'],
            ['arm2', 'arms'],
            ['ass', 'ass'],
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

    public function getBaseCodeForDoll(string $dollName)
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
     * Returns an array of subpart => [[layerIndex, colorChannel]..]
     * @param string $dollName
     * @return array
     * @throws \ImagickException
     */
    public function getDollLayerInformation(string $dollName): array
    {
        if (array_key_exists($dollName, $this->dollLayerInformationCache)) return $this->dollLayerInformationCache[$dollName];
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

    public function getDrawingStepsForAvatar(AvatarInstance $avatar): array
    {
        if (array_key_exists($avatar->code, $this->avatarDrawingStepsCache)) return $this->avatarDrawingStepsCache[$avatar->code];
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
            $parts[$bodyPart] = [
                'dollName' => $dollNames[$bodyPart],
                'doll' => $this->getDoll($dollNames[$bodyPart]),
                'layerInfo' => $this->getDollLayerInformation($dollNames[$bodyPart])
            ];
        }

        // Build drawing steps based off of the subpart array since such is in drawing order
        foreach ($this->subParts as $partInfo) {
            [$subPart, $part] = $partInfo;
            $layerInfo = $parts[$part]['layerInfo'];
            if (array_key_exists($subPart, $layerInfo)) {
                $drawingSteps[] = [
                    'subPart' => $subPart,
                    'dollName' => $parts[$part]['dollName'],
                    'doll' => $parts[$part]['doll'],
                    'layers' => $layerInfo[$subPart]
                ];
            }
        }
        $this->avatarDrawingStepsCache[$avatar->code] = $drawingSteps;
        return $drawingSteps;
    }

    public function renderAvatarInstance(AvatarInstance $avatar): Imagick
    {
        //Create a blank canvas
        $image = new Imagick();
        $image->newImage($this->width, $this->height, 'transparent');
        $image->setImageFormat("png");

        foreach ($this->getDrawingStepsForAvatar($avatar) as $step) {

        }
        return $image;
    }

}
