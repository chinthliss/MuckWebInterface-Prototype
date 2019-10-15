<?php


namespace App\Contracts;
use App\Muck\MuckCharacter;
use Illuminate\Support\Collection;

interface MuckConnection
{

    /**
     * Get all the characters of a given accountId
     * @param int $aid
     * @return null|Collection in the form [characterDbref:[MuckCharacter]]
     */
    public function getCharactersOf(int $aid);

    /**
     * Get characters of present authenticated user
     * @return null|Collection in the form [characterDbref:[MuckCharacter]]
     */
    public function getCharacters();

    //region Auth
    //These functions mimic the equivalent Laravel database calls

    /**
     * If valid, returns an array in the form [aid, MuckCharacter]
     * Worth noting the credentials passed are from the login form so 'email' rather than 'name'.
     * @param array $credentials
     * @return array|null
     */
    public function retrieveByCredentials(array $credentials);

    /**
     * @param MuckCharacter $character
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(MuckCharacter $character, array $credentials);

    /**
     * Identifier is in the form aid:characterDbref
     * If accepted by the muck, will retrieve character details and set on provided User
     * @param string $identifier
     * @return MuckCharacter
     */
    public function retrieveById(string $identifier);

    //endregion Auth

}
