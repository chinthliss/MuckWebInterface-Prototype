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
        if (array_key_exists('email', $credentials) && strtolower($credentials['email']) == 'testcharacter') {
            return [1, MuckCharacter::fromMuckResponse('1234,TestCharacter,100,,wizard')];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateCredentials(MuckCharacter $character, array $credentials)
    {
        if ($character->getDbref() == 1234 && $credentials['password'] == 'password') return true;
        return false;
    }

    /**
     * @inheritDoc
     */
    public function retrieveById(string $identifier)
    {
        if ($identifier == '1:1234') return MuckCharacter::fromMuckResponse('1234,TestCharacter,100,,wizard');
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
                1234 => new MuckCharacter(1234, 'testCharacter')
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
