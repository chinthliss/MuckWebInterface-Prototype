<?php


namespace App\Muck;

use Illuminate\Support\Facades\Log;

class FakeMuckConnection implements MuckConnection
{

    public function __construct(array $config)
    {

    }

    //region Auth Requests

    /**
     * @inheritDoc
     */
    public function retrieveByCredentials(array $credentials)
    {
        Log::debug('MuckCall - retrieveByCredentials: ' . json_encode($credentials));
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
    public function validateCredentials(MuckCharacter $character, array $credentials)
    {
        Log::debug('MuckCall - validateCredentials: ' . json_encode($character)
            . ', ' . json_encode($credentials));
        if ($character->getDbref() == 1234 && $credentials['password'] == 'password') return true;
        if ($character->getDbref() == 1234 && $credentials['password'] == 'password2') return true;
        return false;
    }

    //endregion


    /**
     * @inheritDoc
     */
    public function getCharactersOf(int $aid)
    {
        Log::debug('MuckCall - getCharactersOf: ' . $aid);
        $result = [];
        if ($aid == 1) {
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
    public function getCharacters()
    {
        $user = auth()->user();
        if (!$user || !$user->getAid()) return null;
        return $this->getCharactersOf($user->getAid());
    }

    /**
     * @inheritDoc
     */
    public function usdToAccountCurrency(float $amount): ?int
    {
        Log::debug('MuckCall - usdToAccountCurrency: ' . $amount);
        //Fake value!
        return $amount * 3;
    }

    /**
     * @inheritDoc
     */
    public function adjustAccountCurrency(int $accountId, float $usdAmount,
                                          int $accountCurrency, ?string $subscriptionId): int
    {
        Log::debug('MuckCall - adjustAccountCurrency: ' . $accountId
            . ', ' . $usdAmount . ', ' . $accountCurrency . ', ' . $subscriptionId);
        // Nothing done by the fake method
        Log::debug(
            "Fake Muck adjustAccountCurrency call for AID#" . $accountId
            . ", usdAmount=" . $usdAmount . ", accountCurrency=" . $accountCurrency
            . ", subscriptionId=" . $subscriptionId
        );
        return $accountCurrency;
    }

    /**
     * @inheritDoc
     */
    public function rewardItem(int $accountId, float $usdAmount, int $accountCurrency, string $itemCode): int
    {
        Log::debug('MuckCall - rewardItem: ' . $accountId
            . ', ' . $usdAmount . ', ' . $accountCurrency . ', ' . $itemCode);
        // Nothing done by the fake method
        Log::debug(
            "Fake Muck rewardItem call for AID#" . $accountId
            . ", usdAmount=" . $usdAmount . ", accountCurrency=" . $accountCurrency
            . ", itemCode=" . $itemCode
        );
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
