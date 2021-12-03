<?php


namespace App\Muck;

use App\Helpers\Ansi;
use App\User;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;
use Error;

class HttpMuckConnection implements MuckConnection
{

    private string $salt;
    private Client $client;
    private string $uri;

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

    private function redactForLog(array $credentials): array
    {
        if (array_key_exists('password', $credentials)) $credentials['password'] = '********';
        return $credentials;
    }

    /**
     * @param string $request
     * @param array $data
     * @return string
     */
    protected function requestFromMuck(string $request, array $data = []): string
    {
        Log::debug('requestFromMuck:' . $request . ', request: ' . json_encode($this->redactForLog($data)));
        $data['mwi_request'] = $request;
        $data['mwi_timestamp'] = Carbon::now()->timestamp; //This is to ensure that repeated requests don't match
        $signature = sha1(http_build_query($data) . $this->salt);
        $benchmark = -microtime(true);
        try {
            $result = $this->client->request('POST', $this->uri, [
                'headers' => [
                    'Signature' => $signature
                ],
                'form_params' => $data
            ]);
        } catch (GuzzleException $e) {
            throw new Error("Connection to muck failed - " . $e->getMessage());
        }
        $benchmark += microtime(true);
        $benchmarkText = round($benchmark * 1000.0, 2);
        //getBody() returns a stream, so need to ensure we complete and parse such:
        //The result will also have a trailing \r\n
        $parsedResult = rtrim($result->getBody()->getContents());
        Log::debug("requestFromMuck: $request, time taken: {$benchmarkText}ms, response: " . json_encode($parsedResult));
        return $parsedResult;
    }

    /**
     * @inheritDoc
     */
    public function getCharactersOf(User $user): array
    {
        $characters = [];
        $response = $this->requestFromMuck('getCharacters', ['aid' => $user->getAid()]);
        //Form of result is \r\n separated lines of dbref,name,level,flags
        foreach (explode(chr(13) . chr(10), $response) as $line) {
            if (!trim($line)) continue;
            $character = $this->parseMuckObjectResponse($line);
            $characters[$character->dbref()] = $character;
        }
        return $characters;
    }

    #region Character Creation / Generation

    /**
     * @inheritDoc
     */
    public function getCharacterSlotState(User $user): array
    {
        $response = $this->requestFromMuck('getCharacterSlotState', ['aid' => $user->getAid()]);
        $response = explode(',', $response);
        return [
            "characterSlotCount" => $response[0],
            "characterSlotCost" => $response[1]
        ];
    }

    /**
     * @inheritDoc
     */
    public function buyCharacterSlot(User $user): array
    {
        $response = $this->requestFromMuck('buyCharacterSlot', ['aid' => $user->getAid()]);
        $response = explode(',', $response);

        // On error we get ['ERROR',message]
        if ($response[0] == 'ERROR') {
            return [
                "error" => $response[1]
            ];
        }

        // On success we get ['OK',characterSlotCount,characterSlotCost]
        return [
            "characterSlotCount" => $response[1],
            "characterSlotCost" => $response[2]
        ];
    }

    /**
     * @inheritDoc
     */
    public function findProblemsWithCharacterName(string $name): string
    {
        return $this->requestFromMuck('findProblemsWithCharacterName', ['name' => $name]);
    }

    /**
     * @inheritDoc
     */
    public function findProblemsWithCharacterPassword(string $password): string
    {
        return $this->requestFromMuck('findProblemsWithCharacterPassword', ['password' => $password]);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function createCharacterForUser(string $name, User $user): array
    {
        $response = $this->requestFromMuck('createCharacterForAccount', ['name' => $name, 'aid' => $user->getAid()]);
        $response = explode('|', $response);
        // Response is either:
        //   ERROR|error message
        //   OK|initial password|character object representation
        if ($response[0] != 'OK') {
            throw new Exception(array_key_exists(1, $response) ? $response[1] : 'Connection error with game');
        }
        return [
            "character" => $this->parseMuckObjectResponse( join('|', array_slice($response,2))),
            "initialPassword" => $response[1]
        ];
    }

    // @inheritDoc
    public function finalizeCharacter(array $characterData): array
    {
        $response = $this->requestFromMuck('finalizeNewCharacter', ['characterData' => json_encode($characterData)]);

        if ($response === 'OK') return ['success' => true, 'messages' => []];

        $messages = $response ? explode(chr(13) . chr(10), $response) : ['A server issue occurred'];
        return ['success' => false, 'messages' => $messages];
    }

    public function getCharacterInitialSetupConfiguration(User $user): array
    {
        $response = $this->requestFromMuck('getCharacterInitialSetupConfiguration', ['aid' => $user->getAid()]);
        $config = json_decode($response, true);
        foreach (['factions', 'perks', 'flaws'] as $section) {
            foreach ($config[$section] as &$item) {
                if (array_key_exists('description', $item))
                    $item['description'] = Ansi::unparsedToHtml($item['description']);
            }
        }
        return $config;
    }

    #endregion Character Creation / Generation

    #region Auth Requests

    /**
     * @inheritDoc
     */
    public function validateCredentials(MuckCharacter $character, array $credentials): bool
    {
        if (!array_key_exists('password', $credentials)) return false;
        return $this->requestFromMuck('validateCredentials', [
            'dbref' => $character->dbref(),
            'password' => $credentials['password']
        ]);
    }

    #endregion Auth Requests

    /**
     * @inheritDoc
     */
    public function usdToAccountCurrency(float $usdAmount): ?int
    {
        /** @var User $user */
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
        if ($response > 0) return Carbon::createFromTimestamp($response);
        return null;
    }

    public function findAccountsByCharacterName(string $name): array
    {
        $response = $this->requestFromMuck('findAccountsByCharacterName', ['name' => $name]);
        return $response ? explode(',', $response) : [];
    }

    /**
     * @inheritDoc
     */
    public function changeCharacterPassword(User $user, MuckCharacter $character, string $password): bool
    {
        $response = $this->requestFromMuck('changeCharacterPassword', [
            'aid' => $user->getAid(),
            'dbref' => $character->dbref(),
            'password' => $password
        ]);
        return ($response === 'OK');
    }

    /**
     * Parses a objectToString response from the muck
     * @param string $muckResponse
     * @return MuckDbref|MuckCharacter
     */
    private function parseMuckObjectResponse(string $muckResponse): MuckDbref
    {
        /*
         * Expected format: dbref,creationTimestamp,typeFlag,metadata,name
         * Name is at the end because it can contain commas.
         * Metadata used:
         * Player - aid|level|avatar|colonSeparatedFlags
         * Zombie - level|avatar
         */
        $parts = explode(',', $muckResponse);
        if (count($parts) < 5)
            throw new InvalidArgumentException("parseMuckObjectResponse: Response contains the wrong number of parts: $muckResponse");

        // The first four parts are fixed
        list($dbref, $creationTimestamp, $typeFlag, $metadata) = $parts;
        $metadata = explode('|', $metadata);
        // The name itself can contain commas, so we reassemble any remaining parts
        $name = join(',', array_slice($parts, 4));
        $dbref = intval($dbref);
        $creationTimestamp = Carbon::createFromTimestamp($creationTimestamp);

        switch ($typeFlag) {
            case 'p':
                if (count($metadata) != 4)
                    throw new InvalidArgumentException("parseMuckObjectResponse: Expected 4 items in metadata for a player: $muckResponse");
                list($accountId, $level, $avatar, $flagsAsString) = $metadata;
                $flags = $flagsAsString ? explode(':', $flagsAsString) : [];
                $muckObject = new MuckCharacter($dbref, $name, $creationTimestamp,
                    $level, $avatar, $flags, $accountId);
                break;
            case 'z':
                list($level, $avatar) = $metadata;
                $muckObject = new MuckCharacter($dbref, $name, $creationTimestamp,
                    $level, $avatar);
                break;
            case 'r':
                $muckObject = new MuckDbref($dbref, $name, $typeFlag, $creationTimestamp);
                break;
            case 't':
                $muckObject = new MuckDbref($dbref, $name, $typeFlag, $creationTimestamp);
                break;
            default:
                throw new Error("Code missing to parse the given typeFlag.");
        }
        return $muckObject;
    }

    /**
     * @inheritDoc
     */
    public function getByDbref(int $dbref): ?MuckDbref
    {
        $response = $this->requestFromMuck('getByDbref', ['dbref' => $dbref]);
        if (!$response) return null;

        return $this->parseMuckObjectResponse($response);

    }

    /**
     * @inheritDoc
     */
    public function getByPlayerName(string $name): ?MuckCharacter
    {
        $response = $this->requestFromMuck('getByPlayerName', ['name' => $name]);
        if (!$response) return null;

        return $this->parseMuckObjectResponse($response);
    }

    /**
     * @inheritDoc
     */
    public function getByApiToken(string $apiToken): ?MuckCharacter
    {
        $response = $this->requestFromMuck('getByApiToken', ['api_token' => $apiToken]);
        if (!$response) return null;

        return $this->parseMuckObjectResponse($response);
    }

    public function externalNotification(User $user, MuckCharacter $character, string $message): int
    {
        $count = $this->requestFromMuck('externalNotification',
            ['aid' => $user->getAid(), 'character' => $character->dbref(), 'message' => $message]);
        return (int)$count;
    }
}
