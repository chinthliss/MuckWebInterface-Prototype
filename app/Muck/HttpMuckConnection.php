<?php


namespace App\Muck;
use App\Contracts\MuckConnection;
use App\User;
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
        $data['mwi_timestamp'] = Carbon::now(); //This is to ensure that repeated requests don't match
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
            list($dbref, $characterName, $level, $flags) = explode(',', $line);
            $characters[$dbref] = new MuckCharacter($dbref, $characterName);
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
        if (!array_key_exists('email', $credentials)) return false;
        $response = $this->requestFromMuck('retrieveByCredentials', [
            'name' => $credentials['email']
        ]);
        if (strpos($response, ',')) {
            return User::fromMuckResponse($response);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function validateCredentials(User $user, array $credentials)
    {
        if (!array_key_exists('password', $credentials)
            || !$user->getCharacter()) return false;
        $response = $this->requestFromMuck('validateCredentials', [
            'dbref' => $user->getCharacter()->getDbref(),
            'password' => $credentials['password']
        ]);
        return ($response == 'true');
    }


}
