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
     * Given a character and credentials, asks the muck to verify them (via password)
     * @param MuckCharacter $character
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(MuckCharacter $character, array $credentials);

    //endregion Auth

    public function usdToAccountCurrency(int $amount);

}
