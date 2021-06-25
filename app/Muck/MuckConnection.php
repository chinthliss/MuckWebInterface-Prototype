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
     * Gets parameters required for character select/create
     * @param User $user
     * @return array [characterSlotCount, characterSlotCost]
     */
    public function getCharacterSlotState(User $user): array;

    /**
     * Attempts to buy a character slot.
     * Returns [error] on failure, otherwise [characterSlotCount, characterSlotCost]
     * @param User $user
     * @return array
     */
    public function buyCharacterSlot(User $user): array;

    /**
     * Gets a list of any problems with the specified name being used on the MUCK or an empty list if okay
     * @param string $name
     * @return string
     */
    public function findProblemsWithCharacterName(string $name): string;

    /**
     * Gets a list of any problems with the specified password being used on the MUCK or an empty list if okay
     * @param string $password
     * @return string
     */
    public function findProblemsWithCharacterPassword(string $password): string;

    /**
     * Creates the given character and returns it
     * @param string $name
     * @return array Consists of character and initialPassword
     */
    public function createCharacterForUser(string $name, User $user): array;

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

    /**
     * @param string $name
     * @return int[]
     */
    public function findAccountsByCharacterName(string $name): array;
}
