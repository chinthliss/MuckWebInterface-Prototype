<?php


namespace App\Muck;

use App\Avatar\AvatarService;
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
     * @return MuckCharacter|null
     */
    public function getByPlayerName(string $name): ?MuckCharacter;

    /**
     * Fetches a player object by API token.
     * @param string $apiToken
     * @return MuckCharacter|null
     */
    public function getByApiToken(string $apiToken): ?MuckCharacter;

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

    /**
     * Lets the muck react to a notification sent from the web-side of things.
     * @param User $user
     * @param MuckCharacter|null $character
     * @param string $message
     * @return int Number of notifications sent muck side
     */
    public function externalNotification(User $user, ?MuckCharacter $character, string $message): int;

    /**
     * Fetches an array of each avatar doll and what infections use it
     * @return array<string, array<string>>
     */
    public function avatarDollUsage(): array;

    /**
     * Fetches owned/available gradients/items for a character
     * This requires an array of [itemId:itemRequirementString] to pass to the muck
     * It returns an array of [items: [itemId: status], gradients: [ownedGradient..]]
     *   With status being either 1 for met requirements, 2 for owned and 3 for both
     * @param MuckCharacter $character
     * @param array<string, string> $itemRequirements
     * @return array
     */
    public function getAvatarOptions(MuckCharacter $character, array $itemRequirements): array;

}
