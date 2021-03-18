<?php


namespace App\Muck;

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

    //endregion


    /**
     * @inheritDoc
     */
    public function getCharactersOf(int $aid): ?Collection
    {
        self::fakeMuckCall('getCharactersOf', ['aid' => $aid]);
        $result = [];
        if ($aid === 1) {
            $result = [
                1234 => MuckCharacter::fromMuckResponse('1234,TestCharacter,100,,wizard'),
                2345 => MuckCharacter::fromMuckResponse('2345,TestCharacter2,14,,')
            ];
        }
        return collect($result);
    }

    /**
     * @inheritDoc
     */
    public function getCharacters(): ?Collection
    {
        self::fakeMuckCall('getCharacters');
        $user = auth()->user();
        if (!$user || !$user->getAid()) return null;
        return $this->getCharactersOf($user->getAid());
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
    public function rewardAccountCurrency(int $accountId, int $accountCurrency, string $reason): bool
    {
        self::fakeMuckCall('rewardAccountCurrency', [
            'accountId' => $accountId,
            'accountCurrency' => $accountCurrency,
            'reason' => $reason
        ]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function spendAccountCurrency(int $accountId, int $accountCurrency, string $reason): bool
    {
        self::fakeMuckCall('spendAccountCurrency', [
            'accountId' => $accountId,
            'accountCurrency' => $accountCurrency,
            'reason' => $reason
        ]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function fulfillAccountCurrencyPurchase(int $accountId, float $usdAmount,
                                                   int $accountCurrency, ?string $subscriptionId): int
    {
        self::fakeMuckCall('fulfillAccountCurrencyPurchase', [
            'accountId' => $accountId,
            'usdAmount' => $accountCurrency,
            'accountCurrency' => $accountCurrency,
            'subscriptionId' => $subscriptionId
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
            'usdAmount' => $accountCurrency,
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
        return [
            'progress' => 100,
            'goals' => [ // Keys can't be int in JSON, so also need to be strings here
                '50' => 'This example goal has been achieved',
                '200' => 'This example goal has not been achieved',
                '5000' => 'This example goal has also not been achieved but is longer to test formatting.'
            ]
        ];
    }
}
