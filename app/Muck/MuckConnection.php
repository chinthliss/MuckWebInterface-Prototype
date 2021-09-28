<?php


namespace App\Muck;

use Illuminate\Support\Carbon;
use App\User;

interface MuckConnection
{

    #region Muck object retrieval / verification

    /**
     * Fetches an object by its dbref.
     * @param int $dbref
     * @return MuckDbref|null
     */
    public function getByDbref(int $dbref): ?MuckDbref;


    /**
     * Fetches a player object by name.
     * @param string $name
     * @return MuckDbref|null
     */
    public function getByPlayerName(string $name): ?MuckDbref;

    /**
     * Get all the characters of a given account.
     * @param User $user
     * @return array<int,MuckCharacter>
     */
    public function getCharactersOf(User $user): array;

    #endregion Muck object retrieval / verification


    /**
     * Returns the latest connect or disconnect from any character on the account
     * @param int $aid
     * @return Carbon|null
     */
    public function getLastConnect(int $aid): ?Carbon;

    #region Character Creation / Generation

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
     * @param User $user
     * @return array Consists of character and initialPassword
     */
    public function createCharacterForUser(string $name, User $user): array;

    /**
     * Returns {success, messages} with success being a boolean and messages being an array of strings
     * @param array $characterData
     * @return array
     */
    public function finalizeCharacter(array $characterData): array;

    /**
     * Returns the configuration for character generation.
     * @param User $user
     * @return array
     */
    public function getCharacterInitialSetupConfiguration(User $user): array;

    #endregion Character Creation / Generation

    #region Auth
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

    #endregion Auth

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
     * @return int AmountRewarded
     */
    public function fulfillPatreonSupport(int $accountId, int $accountCurrency): int;

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

    /**
     * @param User $user
     * @param MuckCharacter $character
     * @param string $password
     * @return bool
     */
    public function changeCharacterPassword(User $user, MuckCharacter $character, string $password): bool;
}
