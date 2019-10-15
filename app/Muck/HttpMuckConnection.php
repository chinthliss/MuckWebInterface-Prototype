<?php


namespace App\Muck;
use App\Contracts\MuckConnection;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;

class HttpMuckConnection implements MuckConnection
{

    private $salt = null;
    private $client = null;
    private $uri = null;

    public function __construct(array $config)
    {
        if(!array_key_exists('salt', $config))
            throw new \Exception("Salt hasn't been set in Muck connection config. Ensure MUCK_SALT is set.");
        $this->salt = $config['salt'];
        if (!$config['host'] || !$config['port'] || !$config['uri'])
            throw new \Exception('Configuration for muck is missing host, port or uri');
        $url = ($config['useHttps'] ? 'https' : 'http') . '://' . $config['host'] . ':' . $config['port'];
        $this->client = new Client([
            'base_uri' => $url
        ]);
        $this->uri = $config['uri'];
    }

    protected function requestFromMuck(string $request, array $data = [])
    {
        $data['mwi_request'] = $request;
        $data['mwi_timestamp'] = Carbon::now()->timestamp; //This is to ensure that repeated requests don't match
        $signature = sha1(http_build_query($data) . $this->salt);
        try {
            $result = $this->client->request('POST', $this->uri, [
                'headers' => [
                    'Signature' => $signature
                ],
                'form_params' => $data
            ]);
        } catch (GuzzleException $e) {
            throw $e;
        }
        //getBody() returns a stream, so need to ensure we complete and parse such:
        return $result->getBody()->getContents();
    }

    /**
     * @inheritDoc
     */
    public function getCharactersOf(int $aid)
    {
        $characters = [];
        $response = $this->requestFromMuck('getCharacters', ['aid'=>$aid]);
        //Form of result is \r\n separated lines of dbref,name,level,flags
        foreach(explode(chr(13) . chr(10), $response) as $line) {
            if (!trim($line)) continue;
            $character = MuckCharacter::fromMuckResponse($line);
            $characters[$character->getDbref()] = $character;
        }
        return collect($characters);
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
    public function retrieveByCredentials(array $credentials)
    {
        if (!array_key_exists('email', $credentials)) return null;
        $response = $this->requestFromMuck('retrieveByCredentials', [
            'name' => $credentials['email']
        ]);
        //Muck returns character string but with an extra aid value at the front
        if ($split = strpos($response, ',')) {
            $aid = substr($response, 0, $split);
            $characterString = substr($response, $split + 1);
            return [$aid, MuckCharacter::fromMuckResponse($characterString)];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateCredentials(MuckCharacter $character, array $credentials)
    {
        if (!array_key_exists('password', $credentials)) return false;
        $response = $this->requestFromMuck('validateCredentials', [
            'dbref' => $character->getDbref(),
            'password' => $credentials['password']
        ]);
        return $response;
    }


    /**
     * @inheritDoc
     */
    public function retrieveById(string $identifier)
    {
        if (count($exploded = explode(':', $identifier)) != 2) return null;
        list($aid, $dbref) = $exploded;
        $response = $this->requestFromMuck('retrieveById', [
            'aid' => $aid,
            'dbref' => $dbref
        ]);
        if ($response) {
            return MuckCharacter::fromMuckResponse($response);
        }
        return null;
    }
}
