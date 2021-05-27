<?php


namespace App\Muck;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\User;

interface MuckConnection
{

    /**
     * Get all the characters of a given account
     * @param User $user
     * @return null|Collection in the form [characterDbref:[MuckCharacter]]
     */
    public function getCharactersOf(User $user): ?Collection;

    /**
     * Get characters of present authenticated user
     * @return null|Collection in the form [characterDbref:[MuckCharacter]]
     */
    public function getCharacters(): ?Collection;

    /**
     * Gets parameters required for character select/create
     * @return array [characterSlotCount, characterSlotCost]
     */
    public function getCharacterSlotState(): array;

    /**
     * Returns the latest connect or disconnect from any character on the account
     * @param int $aid
     * @return Carbon|null
     */
    public function getLastConnect(int $aid): ?Carbon;

    //region Auth
    //These functions mimic the equivalent Laravel database calls

    /**
     * If valid, returns an array in the form [aid, MuckCharacter]
     * Worth noting the credentials passed are from the login form so 'email' rather than 'name'.
     * May also be 'api_token' since this route is used for api token validation
     * @param array $credentials
     * @return array|null
     */
    public function retrieveByCredentials(array $credentials): ?array;

    /**
     * Given a character and credentials, asks the muck to verify them (via password)
     * @param MuckCharacter $character
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(MuckCharacter $character, array $credentials): bool;

    /**
     * Gets a character, checking it's valid and belongs to the given account
     * Returns null if it doesn't belong to the given account or isn't valid
     * @param User $user
     * @param int $dbref
     * @return MuckCharacter|null
     */
    public function retrieveAndVerifyCharacterOnAccount(User $user, int $dbref): ?MuckCharacter;

    //endregion Auth

    /**
     * Requests a conversion quote from the muck. Returns null if amount isn't acceptable
     * @param float $usdAmount
     * @return int|null
     */
    public function usdToAccountCurrency(float $usdAmount): ?int;

    /**
     * Asks the muck to handle account currency purchases. Allows for bonuses / monthly contributions / etc.
     * @param int $accountId
     * @param float $usdAmount
     * @param int $accountCurrency
     * @param ?string $subscriptionId
     * @return int accountCurrencyRewarded
     */
    public function fulfillAccountCurrencyPurchase(int $accountId, float $usdAmount, int $accountCurrency, ?string $subscriptionId): int;

    /**
     * @param int $accountId
     * @param int $accountCurrency
     * @return mixed
     */
    public function fulfillPatreonSupport(int $accountId, int $accountCurrency);

    /**
     * @param int $accountId
     * @param float $usdAmount
     * @param int $accountCurrency
     * @param string $itemCode
     * @return int accountCurrencyRewarded
     */
    public function rewardItem(int $accountId, float $usdAmount, int $accountCurrency, string $itemCode): int;

    /**
     * Should return an array with the following properties:
     *   progress(int)
     *   goals(array) - array of amount:description. Amount is a string because of json
     * @return array
     */
    public function stretchGoals(): array;
}
