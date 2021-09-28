<?php

namespace App\Muck;

use App\User;
use Illuminate\Support\Carbon;

/*
 * Acts as:
 *   A cache of verified objects from the muck to save repeated requests to the database.
 *   Verification of cached objects loaded from the database.
 */
class MuckObjectService
{
    private MuckConnection $connection;

    /**
     * Objects should only be added to this after they've been verified
     * @var array<int, MuckDbref>
     */
    private array $byDbref = [

    ];

    /**
     * Objects should only be added to this after they've been verified
     * Only for player objects!
     * @var array<string, MuckCharacter>
     */
    private array $byName = [

    ];

    public function __construct(MuckConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Fetches an object by its dbref.
     * @param int $dbref
     * @return ?MuckDbref
     */
    public function getByDbref(int $dbref): ?MuckDbref
    {
        if (array_key_exists($dbref, $this->byDbref)) return $this->byDbref[$dbref];

        $object = $this->connection->getByDbref($dbref);
        if ($object) $this->byDbref[$object->dbref()] = $object;

        return $object;
    }

    /**
     * Fetches a player object by name.
     * @param string $name
     * @return ?MuckDbref
     */
    public function getByPlayerName(string $name): ?MuckDbref
    {
        if (array_key_exists($name, $this->byName)) return $this->byName[$name];

        $object = $this->connection->getByPlayerName($name);
        if ($object) {
            $this->byDbref[$object->dbref()] = $object;
            $this->byName[$object->name()] = $object;
        }

        return $object;
    }

    /**
     * Get all the characters of a given user.
     * @param User $user
     * @return array<int,MuckCharacter>
     */
    public function getCharactersOf(User $user): array
    {
        $characters = $this->connection->getCharactersOf($user);
        foreach ($characters as $character) {
            $this->byDbref[$character->dbref()] = $character;
            $this->byName[$character->name()] = $character;
        }
        return $characters;
    }
}
