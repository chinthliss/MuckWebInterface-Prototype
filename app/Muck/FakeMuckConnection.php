<?php


namespace App\Muck;

use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FakeMuckConnection implements MuckConnection
{

    public function __construct(array $config)
    {

    }

    /**
     * Just a method to provide unified logging
     * @param string $call
     * @param array $data
     */
    private static function fakeMuckCall(string $call, array $data = [])
    {
        $dataAsString = json_encode($data);
        Log::debug("FakeMuckCall - {$call}: Data={$dataAsString}");
    }

    //region Auth Requests

    /**
     * @inheritDoc
     */
    public function retrieveByCredentials(array $credentials): ?array
    {
        self::fakeMuckCall('retrieveByCredentials', $credentials);
        if (array_key_exists('email', $credentials)) {
            $email = strtolower($credentials['email']);
            if ($email == 'testcharacter')
                return [1, MuckCharacter::fromMuckResponse('1234,TestCharacter,100,,wizard')];
            if ($email == 'testcharacter2')
                return [1, MuckCharacter::fromMuckResponse('2345,TestCharacter2,14,,')];
        }
        if (array_key_exists('api_token', $credentials)) {
            $token = $credentials['api_token'];
            if ($token == 'token_testcharacter')
                return [1, MuckCharacter::fromMuckResponse('1234,TestCharacter,100,,wizard')];
            if ($token == 'token_testcharacter2')
                return [1, MuckCharacter::fromMuckResponse('2345,TestCharacter2,14,,')];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateCredentials(MuckCharacter $character, array $credentials): bool
    {
        self::fakeMuckCall('validateCredentials', $credentials);
        if ($character->getDbref() == 1234 && $credentials['password'] == 'password') return true;
        if ($character->getDbref() == 1234 && $credentials['password'] == 'password2') return true;
        return false;
    }

    public function retrieveAndVerifyCharacterOnAccount(User $user, int $dbref): ?MuckCharacter
    {
        self::fakeMuckCall('verifyAccountHasCharacter', [
            'account' => $user->getAid(),
            'dbref' => $dbref
        ]);
        if ($dbref == '1234' and $user->getAid() === 1)
            return MuckCharacter::fromMuckResponse('1234,TestCharacter,100,,wizard');
        if ($dbref == '2345' and $user->getAid() === 1)
            return MuckCharacter::fromMuckResponse('2345,TestCharacter2,100,,');
        if ($dbref == '3456' and $user->getAid() === 1)
            return MuckCharacter::fromMuckResponse('3456,TestCharacter3,0,,unapproved');
        if ($dbref == '4567' and $user->getAid() === 6)
            return MuckCharacter::fromMuckResponse('4567,TestCharacterA1,0,,unapproved');
        return null;
    }
    //endregion


    /**
     * @inheritDoc
     */
    public function getCharactersOf(User $user): ?Collection
    {
        self::fakeMuckCall('getCharactersOf', ['aid' => $user->getAid()]);
        $result = [];
        if ($user->getAid() === 1) {
            $result = [
                1234 => MuckCharacter::fromMuckResponse('1234,TestCharacter,100,,wizard'),
                2345 => MuckCharacter::fromMuckResponse('2345,TestCharacter2,14,,'),
                3456 => MuckCharacter::fromMuckResponse('3456,TestCharacter3,0,,unapproved')
            ];
        }
        if ($user->getAid() === 6) {
            $result = [
                4657 => MuckCharacter::fromMuckResponse('4567,TestCharacterA1,0,,unapproved')
            ];
        }
        return collect($result);
    }

    /**
     * @inheritDoc
     */
    public function getCharacterSlotState(User $user): array
    {
        self::fakeMuckCall('getCharacterSlotState');
        return [
            "characterSlotCount" => 2,
            "characterSlotCost" => 50
        ];
    }

    /**
     * @inheritDoc
     */
    public function buyCharacterSlot(User $user): array
    {
        self::fakeMuckCall('buyCharacterSlot');
        return [
            "characterSlotCount" => 4,
            "characterSlotCost" => 60
        ];
    }

    /**
     * @inheritDoc
     */
    public function findProblemsWithCharacterName(string $name): string
    {
        self::fakeMuckCall('getAnyIssuesWithCharacterName', ['name' => $name]);
        if (strtolower($name) == 'test') return 'That name is a test.';
        if (str_contains($name, ' ')) return 'That name contains a space.';
        return '';
    }

    /**
     * @inheritDoc
     */
    public function findProblemsWithCharacterPassword(string $password): string
    {
        self::fakeMuckCall('getAnyIssuesWithCharacterPassword', ['password' => $password]);
        if (strtolower($password) == 'test') return 'That password is a test.';
        return '';
    }

    /**
     * @inheritDoc
     */
    public function createCharacterForUser(string $name, User $user): array
    {
        self::fakeMuckCall('createCharacter', ['name' => $name, 'aid' => $user->getAid()]);
        return [
            "character" => new MuckCharacter(4657, 'FakeCharacter'),
            "initialPassword" => 'test'
        ];
    }

    /**
     * @inheritDoc
     */
    public function usdToAccountCurrency(float $usdAmount): ?int
    {
        self::fakeMuckCall('usdToAccountCurrency', ['usdAmount' => $usdAmount]);
        return $usdAmount * 3;
    }

    /**
     * @inheritDoc
     */
    public function fulfillAccountCurrencyPurchase(int $accountId, float $usdAmount,
                                                   int $accountCurrency, ?string $subscriptionId): int
    {
        self::fakeMuckCall('fulfillAccountCurrencyPurchase', [
            'accountId' => $accountId,
            'usdAmount' => $usdAmount,
            'accountCurrency' => $accountCurrency,
            'subscriptionId' => $subscriptionId
        ]);
        return $accountCurrency;
    }

    /**
     * @inheritDoc
     */
    public function fulfillPatreonSupport(int $accountId, int $accountCurrency): int
    {
        self::fakeMuckCall('fulfillPatreonSupport', [
            'accountId' => $accountId,
            'accountCurrency' => $accountCurrency
        ]);
        return $accountCurrency;
    }

    /**
     * @inheritDoc
     */
    public function rewardItem(int $accountId, float $usdAmount, int $accountCurrency, string $itemCode): int
    {
        self::fakeMuckCall('rewardItem', [
            'accountId' => $accountId,
            'usdAmount' => $usdAmount,
            'accountCurrency' => $accountCurrency,
            'itemCode' => $itemCode
        ]);
        return $accountCurrency;
    }

    /**
     * @inheritDoc
     */
    public function stretchGoals(): array
    {
        self::fakeMuckCall('stretchGoals');
        return [
            'progress' => 100,
            'goals' => [ // Keys can't be int in JSON, so also need to be strings here
                '50' => 'This example goal has been achieved',
                '200' => 'This example goal has not been achieved',
                '5000' => 'This example goal has also not been achieved but is longer to test formatting.'
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getLastConnect(int $aid): ?Carbon
    {
        self::fakeMuckCall('getLastConnect', ['aid' => $aid]);
        return Carbon::now();
    }

    /**
     * @inheritDoc
     */
    public function findAccountsByCharacterName(string $name): array
    {
        self::fakeMuckCall('findAccountsByCharacterName', ['name' => $name]);
        if ($name == 'test') return ['TestCharacter' => 1];
        return [];
    }
}
