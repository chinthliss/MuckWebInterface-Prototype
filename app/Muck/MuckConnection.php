<?php


namespace App\Muck;

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
     * May also be 'api_token' since this route is used for api token validation
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

    /**
     * Asks the muck to handle account currency rewards
     * @param int $accountId
     * @param int $usdAmount
     * @param int $accountCurrency
     * @param bool $is_subscription
     * @return int accountCurrencyRewarded
     */
    public function adjustAccountCurrency(int $accountId, int $usdAmount, int $accountCurrency, bool $is_subscription);

}
