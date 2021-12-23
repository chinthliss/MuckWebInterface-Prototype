<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Imagick;

class AvatarService
{
    private string $dollFolder = 'app/avatar/doll/';

    /**
     * @return array
     */
    public function getDolls(): array
    {
        $dolls = [];
        $files = glob(storage_path($this->dollFolder . '*.psd'));
        foreach ($files as $file) {
            $fileName = basename($file);
            $dolls[] = substr($fileName,0,-4); // Remove file extension
        }
        return $dolls;
    }

    public function getDoll($dollName): Imagick
    {
        $filePath = storage_path($this->dollFolder . $dollName . '.psd');
        if (!file_exists($filePath)) throw new \Exception("Specified doll file not found");
        return new Imagick($filePath);
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

        for ($i = $image->getNumberImages() - 1; $i >= 0; $i--) {
            $image->setIteratorIndex($i);
            // Log::debug("File {$dollName}, {$i}: " . json_encode($image->getImageProperties()));
            $label = strtolower($image->getImageProperty('label'));
            // Log::debug("File {$dollName}, {$i} label = " . $label);
            $image->setImageBackgroundColor('transparent');
            // Some files have a separate background layer we don't want
            if (str_starts_with($label, 'background')
                || str_starts_with($label, 'layer')
                || str_starts_with($label, 'bg')) $image->removeImage();
        }

        // Flatten PSD file and create the actual thumbnail
        $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_COALESCE);
        $image->thumbnailImage(100, 0);
        $image->setImageFormat('png');
        return $image;
    }
}
