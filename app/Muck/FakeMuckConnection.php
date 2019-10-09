<?php


namespace App\Muck;
use App\Contracts\MuckConnectionContract;
use Illuminate\Support\Collection;

class FakeMuckConnection implements MuckConnectionContract
{

    public function __construct(array $config)
    {

    }

    /**
     * Get all the characters of a given accountId
     * @param int $aid
     * @return Collection
     */
    public function getCharactersOf(int $aid)
    {
        return collect([
            1234=>new MuckCharacter(1234, 'fakeName')
        ]);
    }

    /**
     * Get characters of present authenticated user
     * @return Collection
     */
    public function getCharacters()
    {
        $user = auth()->user();
        if ( !$user || !$user->getAid() ) return null;
        return collect([
            1234=>new MuckCharacter(1234, 'fakeName')
        ]);
    }
}
