<?php


namespace App\Muck;
use App\Contracts\MuckConnection;

class FakeMuckConnection implements MuckConnection
{

    public function __construct(array $config)
    {

    }

    //region Auth

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

    /**
     * @inheritDoc
     */
    public function retrieveById(string $identifier)
    {
        if ($identifier == '1:1234') return MuckCharacter::fromMuckResponse('1234,TestCharacter,100,,wizard');
        if ($identifier == '1:2345') return MuckCharacter::fromMuckResponse('2345,TestCharacter2,14,,');
        return null;
    }

    //Endregion



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
}
