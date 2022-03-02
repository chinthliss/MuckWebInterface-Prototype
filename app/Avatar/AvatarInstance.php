<?php

namespace App\Avatar;

use App;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * The manifest/configuration for an avatar instance
 * At this stage everything is simple labels/values that a hash can be generated from.
 */
class AvatarInstance
{
    public string $code;

    public function __construct(
        public string  $torso,
        public ?string $head = null,
        public ?string $arms = null,
        public ?string $legs = null,
        public ?string $groin = null,
        public ?string $ass = null,
        public ?string $skin = null, // If set, its default colors replace all bodypart's default colors
        public bool    $male = false,   // Whether to draw male parts
        public bool    $female = false, // Whether to draw female parts
        public ?string $background = null,
        public array   $items = [],
        public ?string $mode = null,
        /**
         * @var string[] Stored as colorName => gradientName (e.g. hair => blonde)
         */
        public array   $colors = []
    )
    {
        $this->code = base64_encode(json_encode($this->toArray()));
    }

    /**
     * @return array
     * @throws Exception
     */
    public function toArray(): array
    {
        $array = [
            'base' => $this->torso
        ];
        if ($this->head) $array['head'] = $this->head;
        if ($this->arms) $array['arms'] = $this->arms;
        if ($this->legs) $array['legs'] = $this->legs;
        if ($this->groin) $array['groin'] = $this->groin;
        if ($this->ass) $array['ass'] = $this->ass;
        if ($this->skin) $array['skin'] = $this->skin;

        if ($this->female) $array['female'] = true;
        if ($this->male) $array['male'] = true;

        if ($this->background) $array['background'] = $this->background;

        if (count($this->colors)) $array['colors'] = $this->colors;
        if (!empty($this->items)) {
            throw new Exception("Items not implemented yet.");
        }

        if ($this->mode) $array['mode'] = $this->mode;
        return $array;
    }

    /**
     * Returns a version of the array where avatar doll details are stripped
     * @return string[]
     * @throws Exception
     */
    public function toCustomizationsOnlyArray(): array
    {
        $array = $this->toArray();
        unset($array['base']);
        unset($array['head']);
        unset($array['arms']);
        unset($array['legs']);
        unset($array['groin']);
        unset($array['ass']);
        unset($array['skin']);
        unset($array['male']);
        unset($array['female']);
        return $array;
    }

    public static function fromArray(array $array): AvatarInstance
    {
        return new AvatarInstance(
            $array['base'],
            $array['head'] ?? null,
            $array['arms'] ?? null,
            $array['legs'] ?? null,
            $array['groin'] ?? null,
            $array['ass'] ?? null,
            $array['skin'] ?? null,
            $array['male'] ?? false,
            $array['female'] ?? false,
            $array['background'] ?? null,
            $array['items'] ?? [],
            $array['mode'] ?? null,
            $array['colors'] ?? []
        );
    }

    public static function fromCode(string $code): AvatarInstance
    {
        $array = json_decode(base64_decode($code), true);
        if (!is_array($array)) throw new Exception("The JSON used to create an AvatarInstance wasn't an array: " . base64_decode($code));
        return self::fromArray($array);
    }

    public static function default(): AvatarInstance
    {
        return new AvatarInstance('FS_Human1');
    }

}
