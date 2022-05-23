<?php

namespace App\Avatar;

use App;
use Exception;

/**
 * The manifest/configuration for an avatar instance
 * At this stage no image resources have been loaded/processed
 */
class AvatarInstance
{
    /**
     * @param string $torso
     * @param string|null $head
     * @param string|null $arms
     * @param string|null $legs
     * @param string|null $groin
     * @param string|null $ass
     * @param string|null $skin
     * @param bool $male
     * @param bool $female
     * @param AvatarItem|null $background
     * @param AvatarItem[] $items Stored as colorName => gradientName (e.g. hair => blonde)
     * @param string[] $colors
     * @param string|null $mode
     * @throws Exception
     */
    public function __construct(
        public string      $torso,
        public ?string     $head = null,
        public ?string     $arms = null,
        public ?string     $legs = null,
        public ?string     $groin = null,
        public ?string     $ass = null,
        public ?string     $skin = null, // If set, its default colors replace all bodypart's default colors
        public bool        $male = false,   // Whether to draw male parts
        public bool        $female = false, // Whether to draw female parts
        public ?AvatarItem $background = null,
        public array       $items = [],
        public array       $colors = [],
        public ?string     $mode = null
    )
    {
        //Ensure no background items are in the foreground (Shouldn't be possible going forward but legacy items)
        foreach ($this->items as $item) {
            if ($item->type === 'background' && $item->z > 1) $item->z = -1;
        }
        //Ensure item list is sorted by z level
        usort($this->items, function ($a, $b) {
            if ($a->z < $b->z) return -1;
            if ($a->z > $b->z) return 1;
            return 0;
        });
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
            $items = [];
            /** @var AvatarItem $item */
            foreach ($this->items as $item) {
                $items[] = $item->toArray();
            }
            $array['items'] = $items;
        }

        if ($this->mode) $array['mode'] = $this->mode;
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
            $array['colors'] ?? [],
            $array['mode'] ?? null
        );
    }

    public static function fromCode(string $code): AvatarInstance
    {
        $array = json_decode(base64_decode($code), true);
        if (!is_array($array)) throw new Exception("The JSON used to create an AvatarInstance wasn't an array: " . base64_decode($code));
        return self::fromArray($array);
    }

    public function toCode(): string
    {
        return base64_encode(json_encode($this->toArray()));
    }

    public static function default(): AvatarInstance
    {
        return new AvatarInstance('FS_Human1');
    }

}
