<?php

namespace App;

use Exception;

/**
 * Holds the configuration for an avatar instance
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
        public ?string $background = null,
        public array   $items = []
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
        if ($this->background) $array['background'] = $this->background;
        if (!empty($this->items)) {
            throw new Exception("Items not implemented yet.");
        }
        return $array;
    }

    public static function fromArray(Array $array): AvatarInstance
    {
        return new AvatarInstance(
            $array['base'],
            $array['head'] ?? null,
            $array['arms'] ?? null,
            $array['legs'] ?? null,
            $array['groin'] ?? null,
            $array['ass'] ?? null,
            $array['background'] ?? null,
            $array['items'] ?? []
        );
    }

    public static function fromCode($code): AvatarInstance
    {
        return self::fromArray(json_decode(base64_decode($code), true));
    }

}
