<?php


namespace App\Muck;

use App\User;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Error;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class HttpMuckConnection implements MuckConnection
{

    private $salt = null;
    private $client = null;
    private $uri = null;

    public function __construct(array $config)
    {
        if (!array_key_exists('salt', $config))
            throw new Error("Salt hasn't been set in Muck connection config. Ensure MUCK_SALT is set.");
        $this->salt = $config['salt'];
        if (!$config['host'] || !$config['port'] || !$config['uri'])
            throw new Error('Configuration for muck is missing host, port or uri');
        $url = ($config['useHttps'] ? 'https' : 'http') . '://' . $config['host'] . ':' . $config['port'];
        $this->client = new Client([
            'base_uri' => $url
        ]);
        $this->uri = $config['uri'];
    }

    /**
     * @param string $request
     * @param array $data
     * @return string
     * @throws GuzzleException
     */
    protected function requestFromMuck(string $request, array $data = []): string
    {
        Log::debug('requestFromMuck calling ' . $request . ' with: ' . json_encode($data));
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
        //The result will also have a trailing \r\n
        $parsedResult = rtrim($result->getBody()->getContents());
        Log::debug('requestFromMuck called ' . $request . ', response: ' . json_encode($parsedResult));
        return $parsedResult;
    }

    /**
     * @inheritDoc
     */
    public function getCharactersOf(User $user): ?Collection
    {
        $characters = [];
        $response = $this->requestFromMuck('getCharacters', ['aid' => $user->getAid()]);
        //Form of result is \r\n separated lines of dbref,name,level,flags
        foreach (explode(chr(13) . chr(10), $response) as $line) {
            if (!trim($line)) continue;
            $character = MuckCharacter::fromMuckResponse($line);
            $characters[$character->getDbref()] = $character;
        }
        return collect($characters);
    }

    /**
     * @inheritDoc
     */
    public function getCharacters(): ?Collection
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$user || !$user->getAid()) return null;
        return $this->getCharactersOf($user);
    }

    //region Auth Requests

    /**
     * @inheritDoc
     */
    public function retrieveByCredentials(array $credentials): ?array
    {
        $response = $this->requestFromMuck('retrieveByCredentials', $credentials);
        //Muck returns character string but with an extra aid value at the front
        if ($split = strpos($response, ',')) {
            $aid = intval(substr($response, 0, $split));
            $characterString = substr($response, $split + 1);
            return [$aid, MuckCharacter::fromMuckResponse($characterString)];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateCredentials(MuckCharacter $character, array $credentials): bool
    {
        if (!array_key_exists('password', $credentials)) return false;
        return $this->requestFromMuck('validateCredentials', [
            'dbref' => $character->getDbref(),
            'password' => $credentials['password']
        ]);
    }

    public function retrieveAndVerifyCharacterOnAccount(User $user, int $dbref): ?MuckCharacter
    {
        $response = $this->requestFromMuck('verifyAccountHasCharacter', [
            'account' => $user->getAid(),
            'dbref' => $dbref
        ]);
        return $response ? MuckCharacter::fromMuckResponse($response) : null;
    }

    // endregion Auth Requests

    /**
     * @inheritDoc
     */
    public function usdToAccountCurrency(float $usdAmount): ?int
    {
        $user = auth()->user();
        if (!$user || !$user->getAid()) return null;

        $response = $this->requestFromMuck('usdToAccountCurrencyFor', [
            'amount' => $usdAmount,
            'account' => $user->getAid()
        ]);
        return (int)$response;
    }

    /**
     * @inheritDoc
     */
    public function fulfillAccountCurrencyPurchase(int $accountId, float $usdAmount,
                                                   int $accountCurrency, ?string $subscriptionId): int
    {
        $response = $this->requestFromMuck('fulfillAccountCurrencyPurchase', [
            'account' => $accountId,
            'usdAmount' => $usdAmount,
            'accountCurrency' => $accountCurrency,
            'subscriptionId' => $subscriptionId
        ]);
        return (int)$response;
    }

    /**
     * @inheritDoc
     */
    public function fulfillPatreonSupport(int $accountId, int $accountCurrency): int
    {
        $response = $this->requestFromMuck('fulfillPatreonSupport', [
            'account' => $accountId,
            'accountCurrency' => $accountCurrency
        ]);
        return (int)$response;
    }

    /**
     * @inheritDoc
     */
    public function rewardItem(int $accountId, float $usdAmount, int $accountCurrency, string $itemCode): int
    {
        $response = $this->requestFromMuck('rewardItem', [
            'account' => $accountId,
            'usdAmount' => $usdAmount,
            'accountCurrency' => $accountCurrency,
            'itemCode' => $itemCode
        ]);
        return (int)$response;
    }

    /**
     * @inheritDoc
     */
    public function stretchGoals(): array
    {
        return json_decode($this->requestFromMuck('stretchGoals'), true);
    }

    /**
     * @inheritDoc
     */
    public function getLastConnect(int $aid): ?Carbon
    {
        $response = $this->requestFromMuck('getLastConnect', ['aid' => $aid]);
        if ($response > 0) return Carbon::createFromTimestampUTC($response);
        return null;
    }

}
