<?php

namespace App\Avatar;

use App\Muck\MuckCharacter;
use App\Muck\MuckConnection;
use Exception;
use Illuminate\Support\Facades\Log;
use Imagick;
use ImagickException;

class AvatarService
{

    const DOLL_FILE_LOCATION = 'app/avatar/doll/';
    const ITEM_FILE_LOCATION = 'app/avatar/item/';
    const BACKGROUND_FILE_LOCATION = 'app/avatar/background/';

    const DOLL_WIDTH = 384;
    const DOLL_HEIGHT = 640;
    const GRADIENT_SIZE = 2048; // Aiming for 10-bit, since that's growing in usage

    const MODE_HEAD_ONLY = 'head_only';
    const MODE_EXPLICIT = 'explicit';
    const MODE_SAFE = 'safe';

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

    const FEMALE_ONLY_SUBPARTS = ['breasts', 'nipples'];
    const MALE_ONLY_SUBPARTS = ['sheath', 'penis'];

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

    /**
     * @var array<string, AvatarDoll>
     */
    private array $avatarDollCache = [];

    /**
     * @var array<string, AvatarDollDrawingStep[]>
     */
    private array $avatarDrawingPlanCache = [];

    public function __construct(
        private AvatarProvider $provider
    )
    {
    }

    #region AvatarDoll loading/processing

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

    /**
     * Only for internal optimised route that don't require full processing of a doll (e.g. admin thumbnails)
     * Everything else should use getDoll to load the full information in.
     * @param $dollName
     * @return Imagick
     * @throws ImagickException
     */
    private function getDollImage($dollName): Imagick
    {
        $filePath = $this->getDollFileName($dollName);
        Log::debug("(Avatar) getDollImage loading PSD file from " . $filePath);
        if (!file_exists($filePath)) throw new Exception("Specified doll file not found - " . $dollName);
        return new Imagick($filePath);
    }

    /**
     * Calculates a breakdown of the layers in a doll and the order they need to be drawn in
     * Returns an array of [subpart => [layerIndex, colorChannel]]
     * @param AvatarDoll $doll
     * @return array<string, array>
     * @throws ImagickException
     */
    private function getDollLayerDrawingOrder(AvatarDoll $doll): array
    {
        Log::debug("(Avatar) Calculating layer drawing order for doll $doll->name");
        $array = [];
        for ($i = 1; $i < $doll->image->getNumberImages(); $i++) {
            $doll->image->setIteratorIndex($i);
            $layerName = strtolower($doll->image->getImageProperty('label'));
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

        return $array;
    }

    /**
     * Gets the default gradients for a doll (from the PSD file or cache)
     * @param AvatarDoll $doll
     * @return AvatarGradient[] Array of 5 gradients with the indexes matching the ones referenced in the PSD
     * @throws Exception
     */
    private function getDollDefaultGradientInformation(AvatarDoll $doll): array
    {
        Log::debug("(Avatar) getDollDefaultGradientInformation loading PSD file for $doll->name");
        $benchmark = -microtime(true);

        $filePath = $this->getDollFileName($doll->name);
        if (!file_exists($filePath)) throw new Exception("Specified doll file not found - " . $doll->name);

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
                    "_" . $doll->name . "_" . $index,
                    "Internally generated gradient",
                    $steps,
                    true
                );
                $result[$index] = $gradient;
            }
        }

        //Put something in for anything that we didn't find
        for ($i = 0; $i < count($result); $i++) {
            if (!$result[$i]) $result[$i] = new AvatarGradient(
                "_identity",
                "Internally generated gradient",
                [[0, 0, 0, 0], [255, 255, 255, 255]],
                true
            );
        }

        $benchmark += microtime(true);
        $benchmarkText = round($benchmark * 1000.0, 2);
        Log::debug("(Avatar)   Time taken to load default gradients: {$benchmarkText}ms");
        return $result;
    }

    public function getDoll($dollName): AvatarDoll
    {
        if (array_key_exists($dollName, $this->avatarDollCache)) return $this->avatarDollCache[$dollName];
        Log::debug("(Avatar) Loading and processing information for doll $dollName");

        $image = $this->getDollImage($dollName);
        $doll = new AvatarDoll($dollName, $image);
        $doll->drawingInformation = $this->getDollLayerDrawingOrder($doll);
        $doll->defaultGradients = $this->getDollDefaultGradientInformation($doll);
        $this->avatarDollCache[$dollName] = $doll;
        return $doll;
    }

    public function getBaseCodeForDoll(string $dollName, bool $male = false, bool $female = false): string
    {
        $avatar = new AvatarInstance($dollName, male: $male, female: $female);
        return $avatar->code;
    }

    /**
     * Intended to allow a deliberately unshaded thumbnail for a doll list. Does NOT cache!
     * @param string $dollName
     * @return Imagick
     * @throws ImagickException
     */
    public function getDollThumbnail(string $dollName): Imagick
    {
        Log::debug("(Avatar) Getting Doll Thumbnail for $dollName");
        $image = $this->getDollImage($dollName);

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

    // Intended to test how things line up
    public function getAnimatedGifOfAllAvatarDolls(): Imagick
    {
        Log::debug("(Avatar) Creating an animated gif of all dolls!");
        $finalImage = new Imagick();
        $finalImage->setFormat('gif');
        $finalImage->setBackgroundColor('transparent');

        $avatarNames = $this->getDollNames();
        foreach ($avatarNames as $avatarName) {
            Log::debug("(Avatar) Adding to gif - " . $avatarName);
            $image = $this->renderAvatarInstance(new AvatarInstance($avatarName));
            $image->setImageDelay(10); // Specified in 1/100 of a second
            $image->setImageDispose(IMAGICK::DISPOSE_BACKGROUND); //What the animation starts the next frame with
            $finalImage->addImage($image);
        }
        return $finalImage->coalesceImages();
    }

    #endregion AvatarDoll loading/processing

    #region Avatar Items
    /**
     * @return AvatarItem[]
     */
    public function getAvatarItems(): array
    {
        return $this->provider->getItems();
    }

    public function getAvatarItemFilePath(AvatarItem $item): string
    {
        if ($item->type === 'background')
            return storage_path(self::BACKGROUND_FILE_LOCATION . $item->filename);
        else
            return storage_path(self::ITEM_FILE_LOCATION . $item->filename);
    }

    /**
     * @param string $id
     * @return AvatarItem|null
     */
    public function getAvatarItem(string $id): ?AvatarItem
    {
        return $this->provider->getItem($id);
    }

    /**
     * @param $dollName
     * @return Imagick
     * @throws ImagickException
     */
    public function getAvatarItemImage(AvatarItem $item): Imagick
    {
        $filePath = $this->getAvatarItemFilePath($item);
        Log::debug("(Avatar) getAvatarItemImage loading PSD file from " . $filePath);
        if (!file_exists($filePath)) throw new Exception("Specified item file not found - " . $filePath);
        return new Imagick($filePath);
    }


    // Returns a list of every file in the avatar item directories and whether they're used or not
    public function getAvatarItemFileUsage(): array
    {
        $filesInUse = array_map(function ($item) {
            return strtolower($item->filename);
        }, $this->provider->getItems());

        $files = array_merge(
            glob(storage_path(self::ITEM_FILE_LOCATION . '*.png')),
            glob(storage_path(self::BACKGROUND_FILE_LOCATION . '*.png'))
        );

        $usage = array_map(function ($file) use ($filesInUse) {
            return [
                'filename' => $file,
                'inUse' => in_array(strtolower(basename($file)), $filesInUse)
            ];
        }, $files);

        return $usage;
    }

    /**
     * @param AvatarItem $item
     * @return Imagick
     * @throws ImagickException
     */
    public function renderAvatarItemPreview(AvatarItem $item): Imagick
    {
        $image = $this->getAvatarItemImage($item);
        //Items are only returned as a 64 x 64 preview image
        $image->thumbnailImage(128, 64, true);
        return $image;
    }
    #endregion Avatar Items

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
        Log::debug("(Avatar) Rendering Image for gradient $gradient->name");
        $benchmark = -microtime(true);
        //Holding image
        $image = new Imagick();

        $stepCount = count($gradient->steps); // Just for readability

        //Starting from 1 because we want to render from the previous step to this one
        for ($i = 1; $i < $stepCount; $i++) {
            $fromStep = $gradient->steps[$i - 1];
            $toStep = $gradient->steps[$i];
            //Step values and colors are in the range 0..255
            $fromPixel = (int)($fromStep[0] * self::GRADIENT_SIZE / 255.0);
            $toPixel = (int)($toStep[0] * self::GRADIENT_SIZE / 255.0);
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

        $benchmark += microtime(true);
        $benchmarkText = round($benchmark * 1000.0, 2);
        Log::debug("(Avatar)   Time taken to render gradient: {$benchmarkText}ms");
        return $image;
    }

    // Separate call because these are rendered on demand
    public function getRenderedDefaultGradient(AvatarDoll $doll, int $index): Imagick
    {
        if ($doll->renderedGradients[$index]) return $doll->renderedGradients[$index];
        Log::debug("(Avatar) Rendering default gradient for doll $doll->name, index $index");
        $image = $this->renderGradientImage($doll->defaultGradients[$index]);
        $doll->renderedGradients[$index] = $image;
        return $image;
    }

    public function renderGradientAvatarPreview(AvatarGradient $gradient): Imagick
    {
        $avatar = new AvatarInstance('FS_Husky', mode: self::MODE_HEAD_ONLY);
        $renderedGradient = $this->renderGradientImage($gradient);
        $colorOverrides = [$renderedGradient, $renderedGradient, null, $renderedGradient, null];
        $drawingPlan = $this->getDrawingPlanForAvatarInstance($avatar, colorOverrides: $colorOverrides);
        return $this->renderAvatarDollFromDrawingPlan($drawingPlan);
    }
    #endregion Gradients

    #region Avatar Instances

    public function muckAvatarStringToAvatarInstance(string $string): AvatarInstance
    {
        Log::debug("Converting MuckAvatarString to AvatarInstance: " . $string);
        //String is a ';' separated set of key=value entries
        $array = [];
        $colors = [];
        $items = [];
        foreach (explode(';', $string) as $entry) {
            [$key, $value] = explode('=', $entry, 2);
            switch ($key) {
                //Bodyparts
                case 'torso':
                    $array['base'] = $value;
                    break;
                case 'head':
                    $array['head'] = $value;
                    break;
                case 'arms':
                    $array['arms'] = $value;
                    break;
                case 'legs':
                    $array['legs'] = $value;
                    break;
                case 'ass':
                    $array['ass'] = $value;
                    break;
                case 'cock':
                    $array['groin'] = $value;
                    break;
                case 'skin':
                    $array['skin'] = $value;
                    break;
                //Colors
                case self::COLOR_PRIMARY:
                    $colors[self::COLOR_PRIMARY] = $value;
                    break;
                case self::COLOR_SECONDARY:
                    $colors[self::COLOR_SECONDARY] = $value;
                    break;
                case self::COLOR_NAUGHTY_BITS:
                    $colors[self::COLOR_NAUGHTY_BITS] = $value;
                    break;
                case self::COLOR_HAIR:
                    $colors[self::COLOR_HAIR] = $value;
                    break;
                case self::COLOR_EYES:
                    $colors[self::COLOR_EYES] = $value;
                    break;
                //Items
                case 'item':
                    [$id, $x, $y, $z, $scale, $rotate] = explode('/', $value, 6);
                    if ($scale <= 0.0) $scale = 1.0;
                    $item = $this->getAvatarItem($id);
                    if ($item) {
                        $item->x = $x;
                        $item->y = $y;
                        $item->z = $z;
                        $item->scale = $scale;
                        $item->rotate = $rotate;
                        array_push($items, $item);
                    }
                    break;

                //The Naughty Bits
                case 'male':
                    $array['male'] = $value;
                    break;
                case 'female':
                    $array['female'] = $value;
                    break;

                default:
                    Log::warning('Unknown key encountered in MuckAvatarString - ' . $key . "=" . $value);
            }
        }

        if (count($colors)) $array['colors'] = $colors;
        if (count($items)) $array['items'] = $items;
        if (!array_key_exists('base', $array))
            throw new Exception("fromMuckString was given a string that didn't contain an avatar base (torso)!");

        return AvatarInstance::fromArray($array);
    }

    /**
     * Return an array, in order of drawing, of:
     * [ dollName, doll, subPart, layers ]
     * Layers is an array of [ colorChannel, layerIndex ]
     * @param AvatarInstance $avatarInstance
     * @param null|array $colorOverrides Optional array of rendered gradients.
     * @return AvatarDollDrawingStep[]
     */
    public function getDrawingPlanForAvatarInstance(AvatarInstance $avatarInstance, array $colorOverrides = null): array
    {
        if (array_key_exists($avatarInstance->code, $this->avatarDrawingPlanCache)) return $this->avatarDrawingPlanCache[$avatarInstance->code];
        $benchmark = -microtime(true);
        Log::debug("(Avatar) Calculating drawing plan for " . json_encode($avatarInstance->toArray()));

        $drawingSteps = [];

        // Get a collection of which doll to use for which part
        $dollNames = [
            'torso' => $avatarInstance->torso,
            'head' => $avatarInstance->head ?? $avatarInstance->torso,
            'arms' => $avatarInstance->arms ?? $avatarInstance->torso,
            'legs' => $avatarInstance->legs ?? $avatarInstance->torso,
            'groin' => $avatarInstance->groin ?? $avatarInstance->torso,
            'ass' => $avatarInstance->ass ?? $avatarInstance->torso
        ];

        // Get a collection of the required dolls (along with its processing information) for each bodypart
        // Since these are cached we don't need to go out of our way to avoid duplicate loading
        /** @var array<string, AvatarDoll> $dollsByBodyPart */
        $dollsByBodyPart = [];
        foreach (self::AVATARDOLL_BODYPARTS as $bodyPart) {
            // Head only mode, don't need to process other parts
            if ($avatarInstance->mode == self::MODE_HEAD_ONLY && $bodyPart != 'head') continue;
            // Safe mode - nothing explicit drawn, don't need to bring in the doll for such
            if ($avatarInstance->mode == self::MODE_SAFE && $bodyPart == 'groin') continue;
            $dollsByBodyPart[$bodyPart] = $this->getDoll($dollNames[$bodyPart]);
        }

        // Get a collection of the overridden gradients. Order is important since they're referred by index
        if (!$colorOverrides) $colorOverrides = [null, null, null, null, null];
        $skinOverride = $avatarInstance->skin ? $this->getDoll($avatarInstance->skin) : null;
        foreach (self::COLOR_INDEX_VALUES as $color => $index) {
            if (array_key_exists($color, $avatarInstance->colors)) {
                $gradient = $this->getGradient($avatarInstance->colors[$color]);
                if ($gradient) {
                    $colorOverrides[$index] = $this->renderGradientImage($gradient);
                }
            }
            if (!$colorOverrides[$index] && $skinOverride) $colorOverrides[$index] = $this->getRenderedDefaultGradient($skinOverride, $index);
        }

        // Build drawing plan based off of the subpart array since such is in drawing order
        foreach (self::AVATARDOLL_SUBPARTS as $partInfo) {
            [$subPart, $part] = $partInfo;

            // Explicit mode - If NOT in this mode, skip drawing the lewdest of parts!
            if (!$avatarInstance->mode == self::MODE_EXPLICIT && $subPart == 'penis') continue;
            // Male/female parts only shown if enabled and safe mode isn't on
            if ((!$avatarInstance->female || $avatarInstance->mode == self::MODE_SAFE) && in_array($subPart, self::FEMALE_ONLY_SUBPARTS)) continue;
            if ((!$avatarInstance->male || $avatarInstance->mode == self::MODE_SAFE) && in_array($subPart, self::MALE_ONLY_SUBPARTS)) continue;

            if (!array_key_exists($part, $dollsByBodyPart)) continue;
            if (array_key_exists($subPart, $dollsByBodyPart[$part]->drawingInformation)) {
                $colors = [];
                foreach (self::COLOR_INDEX_VALUES as $color => $index) {
                    $colors[$index] = $colorOverrides[$index] ?: $this->getRenderedDefaultGradient($dollsByBodyPart[$part], $index);
                }
                $drawingSteps[] = new AvatarDollDrawingStep(
                    $dollsByBodyPart[$part]->name, $dollsByBodyPart[$part]->image,
                    $part, $subPart,
                    $dollsByBodyPart[$part]->drawingInformation[$subPart], $colors
                );
            }
        }
        $this->avatarDrawingPlanCache[$avatarInstance->code] = $drawingSteps;
        $benchmark += microtime(true);
        $benchmarkText = round($benchmark * 1000.0, 2);
        Log::debug("(Avatar) Total time taken to calculate drawing plan: {$benchmarkText}ms");
        return $drawingSteps;
    }

    /**
     * Internal function to handle shared parts of rendering an avatar
     * @param AvatarDollDrawingStep[] $drawingPlan
     * @return Imagick
     * @throws ImagickException
     */
    private function renderAvatarDollFromDrawingPlan(array $drawingPlan): Imagick
    {
        $benchmark = -microtime(true);
        Log::debug("(Avatar) Rendering an avatar doll from a drawing plan with " . count($drawingPlan) . " steps.");
        //Create a blank canvas
        $image = new Imagick();
        $image->newImage(self::DOLL_WIDTH, self::DOLL_HEIGHT, 'transparent');
        $image->setImageFormat("png");

        foreach ($drawingPlan as $step) {

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
        $benchmark += microtime(true);
        $benchmarkText = round($benchmark * 1000.0, 2);
        Log::debug("(Avatar) Total time taken rendering a drawing plan: {$benchmarkText}ms");
        return $image;
    }

    public function renderAvatarInstance(AvatarInstance $instance): Imagick
    {
        $avatarDoll = $this->renderAvatarDollFromDrawingPlan($this->getDrawingPlanForAvatarInstance($instance));

        //If there's no items, just return as is
        if (!$instance->items) return $avatarDoll;

        //Otherwise, we have some compositing to do
        $benchmark = -microtime(true);
        Log::debug("(Avatar) Compositing a final avatar with " . count($instance->items) . " item(s)");

        $finalImage = new Imagick();
        $finalImage->setFormat('png');
        $finalImage->newImage(self::DOLL_WIDTH, self::DOLL_HEIGHT, 'transparent');

        $drawnAvatar = false;
        for ($i = 0; $i < count($instance->items); $i++) {
            $item = $instance->items[$i];
            if (!$drawnAvatar && $item->z > 0) {
                $drawnAvatar = true;
                $finalImage->compositeImage($avatarDoll, Imagick::COMPOSITE_OVER, 0, 0);
            }
            Log::debug("(Avatar)   Rendering item " . json_encode($item->toArray()));
            $itemImage = $this->getAvatarItemImage($item);
            $itemImage->setGravity(Imagick::GRAVITY_CENTER);
            $width = $itemImage->getImageWidth() * $item->scale;
            $height = $itemImage->getImageHeight() * $item->scale;

            if ($item->scale <> 1.0) $itemImage->scaleImage($width, $height);
            if ($item->rotate <> 0) $itemImage->rotateImage('transparent', $item->rotate);

            //Rotating and scaling will have changed the image size
            $widthOffset = ($width - $itemImage->getImageWidth()) / 2;
            $heightOffset = ($height - $itemImage->getImageHeight()) / 2;

            $finalImage->compositeImage($itemImage, Imagick::COMPOSITE_OVER,
                $item->x + $widthOffset, $item->y + $heightOffset);
        }
        if (!$drawnAvatar) $finalImage->addImage($avatarDoll);


        $benchmark += microtime(true);
        $benchmarkText = round($benchmark * 1000.0, 2);
        Log::debug("(Avatar) Total time taken rendering a drawing plan: {$benchmarkText}ms");
        return $finalImage;
    }

    #endregion Avatar Instances

    /**
     * Returns an array of [gradients, items, backgrounds] available to the given character
     * @param MuckConnection $muck
     * @param MuckCharacter $character
     * @return array
     * @throws Exception
     */
    public function getAvatarOptions(MuckConnection $muck, MuckCharacter $character): array
    {
        // Need to pick up ownership and whether someone meets the requirements for things from the muck
        $itemCatalog = $this->getAvatarItems();
        $requirements = [];
        foreach ($itemCatalog as $item) {
            if ($item->requirement) $requirements[$item->id] = $item->requirement;
        }
        $muckResponse = $muck->getAvatarOptionsFor($character, $requirements);

        if (!array_key_exists('gradients', $muckResponse) || !array_key_exists('items', $muckResponse))
            throw new \Exception("Muck response was missing an expected part!");

        // Gradients
        // Format is gradientName:[availableBodyParts]
        $gradients = [];
        $ownedGradients = $muckResponse['gradients'];
        foreach ($this->getGradients() as $gradient) {
            $available = [];
            foreach (["fur", "skin", "hair", "eyes"] as $color) {
                if (
                    $gradient->free
                    || ($gradient->owner && $gradient->owner === $character->aid())
                    || (array_key_exists($gradient->name, $ownedGradients) && in_array($color, $ownedGradients[$gradient->name]))
                )
                    $available[] = $color;
            }
            $gradients[$gradient->name] = $available;
        }
        Log::debug("Avatar gradient ownership for $character: " . json_encode($gradients));

        // Items (And backgrounds)
        // Format is an array from the item itself but also included 'earned' and 'owner' flags
        $items = [];
        $backgrounds = [];
        foreach ($itemCatalog as $item) {
            $array = $item->toCatalogArray();
            $earned = false;
            $owner = false;
            if (array_key_exists($item->id, $muckResponse['items'])) {
                if ($muckResponse['items'][$item->id] & 1) $earned = true;
                if ($muckResponse['items'][$item->id] & 2) $owner = true;
            }
            $array['requirement'] = $item->requirement ? true : false;
            $array['earned'] = $earned;
            $array['owner'] = $owner;
            if ($item->type === 'background')
                $backgrounds[] = $array;
            else
                $items[] = $array;
        }
        Log::debug("Avatar item ownership for $character: " . json_encode($items));
        Log::debug("Avatar background ownership for $character: " . json_encode($backgrounds));

        return [
            'gradients' => $gradients,
            'items' => $items,
            'backgrounds' => $backgrounds
        ];
    }
}
