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
        if ( !$user || !$user->getAid() ) return null;
        return $this->getCharactersOf($user->getAid());
    }

    /**
     * @inheritDoc
     */
    public function usdToAccountCurrency(int $amount)
    {
        //Fake value!
        return $amount * 3;
    }

    /**
     * @inheritDoc
     */
    public function adjustAccountCurrency(int $accountId, int $usdAmount,
                                          int $accountCurrency, ?string $subscriptionId): int
    {
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
    public function rewardItem(int $accountId, int $usdAmount, int $accountCurrency, string $itemCode): int
    {
        // Nothing done by the fake method
        Log::debug(
            "Fake Muck rewardItem call for AID#" . $accountId
            . ", usdAmount=" . $usdAmount . ", accountCurrency=" . $accountCurrency
            . ", itemCode=" . $itemCode
        );
        return $accountCurrency;
    }
}
