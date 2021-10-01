<?php

namespace App\Muck;

use App\User;
use Illuminate\Support\Facades\Log;

/*
 * Acts as:
 *   A cache of verified objects from the muck to save repeated requests to the database.
 *   Verification of cached objects loaded from the database.
 */

class MuckObjectService
{
    private MuckConnection $connection;
    private MuckObjectsProvider $provider;

    /**
     * Objects should only be added to this after they've been verified
     * @var array<int, MuckDbref>
     */
    private array $byDbref = [];

    /**
     * Objects should only be added to this after they've been verified
     * Only for player objects!
     * @var array<string, MuckCharacter>
     */
    private array $byName = [];

    /**
     * Objects should only be added to this after they've been verified
     * @var array<int, MuckDbref>
     */
    private array $byMuckObjectId = [];

    public function __construct(MuckConnection $connection, MuckObjectsProvider $provider)
    {
        $this->connection = $connection;
        $this->provider = $provider;
    }

    /**
     * Fetches an object by its dbref.
     * @param int $dbref
     * @return MuckDbref|null
     */
    public function getByDbref(int $dbref): ?MuckDbref
    {
        Log::debug("MuckObjectService.getByDbref called for $dbref");

        if (array_key_exists($dbref, $this->byDbref)) {
            $object = $this->byDbref[$dbref];
            Log::debug("MuckObjectService.getByDbref found existing object - $dbref: $object");
            return $object;
        }

        $object = $this->connection->getByDbref($dbref);
        if ($object) $this->byDbref[$object->dbref()] = $object;

        Log::debug("MuckObjectService.getByDbref looked up - $dbref: $object");
        return $object;
    }

    /**
     * Fetches a player object by name.
     * @param string $name
     * @return MuckCharacter|null
     */
    public function getByPlayerName(string $name): ?MuckCharacter
    {
        Log::debug("MuckObjectService.getByPlayerName called for: $name");

        if (array_key_exists($name, $this->byName)) {
            $object = $this->byName[$name];
            Log::debug("MuckObjectService.getByPlayerName found existing object - $name: $object");
            return $object;
        }

        $object = $this->connection->getByPlayerName($name);
        if ($object) {
            $this->byDbref[$object->dbref()] = $object;
            $this->byName[$object->name()] = $object;
        }

        Log::debug("MuckObjectService.getByPlayerName looked up - $name: $object");
        return $object;
    }

    /**
     * Fetches a player object by their API token
     * @param string $apiToken
     * @return MuckCharacter|null
     */
    public function getByApiToken(string $apiToken): ?MuckCharacter
    {
        // No cache to look through for the API token as we'd only be using it during page load
        // But we still cache the results
        $object = $this->connection->getByApiToken($apiToken);
        if ($object) {
            $this->byDbref[$object->dbref()] = $object;
            $this->byName[$object->name()] = $object;
        }

        return $object;
    }

    /**
     * Lookup the object for the given MuckObjectId
     * @param string $id
     * @return MuckDbref|null
     */
    public function getByMuckObjectId(string $id): ?MuckDbref
    {
        Log::debug("MuckObjectService.getByMuckObjectId called for $id");

        if (array_key_exists($id, $this->byMuckObjectId)) {
            $object = $this->byMuckObjectId[$id];
            Log::debug("MuckObjectService.getByMuckObjectId returning already fetched object - $id: $object");
            return $object;
        }
        $object = $this->provider->getById($id);
        if ($object) {
            $this->byMuckObjectId[$id] = $object;
            $this->byDbref[$object->dbref()] = $object;
            if ($object->typeFlag() === 'p') $this->byName[$object->name()] = $object;
        }

        Log::debug("MuckObjectService.getByMuckObjectId fetched object - $id: $object");
        return $object;
    }

    /**
     * Get (or create) the MuckObjectId for the given object
     * @param MuckDbref $object
     * @return int
     */
    public function getMuckObjectIdFor(MuckDbref $object): int
    {
        Log::debug("MuckObjectService.getMuckObjectIdFor called for $object");

        //Check if already fetched
        foreach ($this->byMuckObjectId as $id => $fetchedObject) {
            if ($object == $fetchedObject) {
                Log::debug("MuckObjectService.getMuckObjectIdFor returning already fetched id - $object: $id");
                return $id;
            }
        }
        $id = $this->provider->getIdFor($object);
        $this->byMuckObjectId[$id] = $object;
        Log::debug("MuckObjectService.getMuckObjectIdFor fetched id - $object: $id");
        return $id;
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
