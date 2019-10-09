<?php


namespace App\Muck;
use App\Contracts\MuckConnectionContract;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;

class HttpMuckConnection implements MuckConnectionContract
{

    private $url = null;
    private $password = null;

    public function __construct(array $config)
    {
        if (!$config['host'] || !$config['port']) throw new \Exception('Configuration for muck is missing host and/or port');
        $this->url = ($config['useHttps']?'https':'http') . '://' . $config['host'] . ':' . $config['port'];
        $this->password = $config['password'];
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
