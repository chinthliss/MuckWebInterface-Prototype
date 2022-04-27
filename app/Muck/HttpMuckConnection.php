<?php


namespace App\Muck;

use App\Avatar\AvatarService;
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
    protected function requestToMuck(string $request, array $data = []): string
    {
        Log::debug('requestToMuck:' . $request . ', request: ' . json_encode($this->redactForLog($data)));
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
        Log::debug("requestToMuck: $request, time taken: {$benchmarkText}ms, response: " . json_encode($parsedResult));
        return $parsedResult;
    }

    /**
     * @inheritDoc
     */
    public function getCharactersOf(User $user): array
    {
        $characters = [];
        $response = $this->requestToMuck('getCharacters', ['aid' => $user->getAid()]);
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
        $response = $this->requestToMuck('getCharacterSlotState', ['aid' => $user->getAid()]);
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
        $response = $this->requestToMuck('buyCharacterSlot', ['aid' => $user->getAid()]);
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
        return $this->requestToMuck('findProblemsWithCharacterName', ['name' => $name]);
    }

    /**
     * @inheritDoc
     */
    public function findProblemsWithCharacterPassword(string $password): string
    {
        return $this->requestToMuck('findProblemsWithCharacterPassword', ['password' => $password]);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function createCharacterForUser(string $name, User $user): array
    {
        $response = $this->requestToMuck('createCharacterForAccount', ['name' => $name, 'aid' => $user->getAid()]);
        $response = explode('|', $response);
        // Response is either:
        //   ERROR|error message
        //   OK|initial password|character object representation
        if ($response[0] != 'OK') {
            throw new Exception(array_key_exists(1, $response) ? $response[1] : 'Connection error with game');
        }
        return [
            "character" => $this->parseMuckObjectResponse(join('|', array_slice($response, 2))),
            "initialPassword" => $response[1]
        ];
    }

    // @inheritDoc
    public function finalizeCharacter(array $characterData): array
    {
        $response = $this->requestToMuck('finalizeNewCharacter', ['characterData' => json_encode($characterData)]);

        if ($response === 'OK') return ['success' => true, 'messages' => []];

        $messages = $response ? explode(chr(13) . chr(10), $response) : ['A server issue occurred'];
        return ['success' => false, 'messages' => $messages];
    }

    public function getCharacterInitialSetupConfiguration(User $user): array
    {
        $response = $this->requestToMuck('getCharacterInitialSetupConfiguration', ['aid' => $user->getAid()]);
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
        return $this->requestToMuck('validateCredentials', [
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

        $response = $this->requestToMuck('usdToAccountCurrencyFor', [
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
        $response = $this->requestToMuck('fulfillAccountCurrencyPurchase', [
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
        $response = $this->requestToMuck('fulfillPatreonSupport', [
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
        $response = $this->requestToMuck('rewardItem', [
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
        return json_decode($this->requestToMuck('stretchGoals'), true);
    }

    /**
     * @inheritDoc
     */
    public function getLastConnect(int $aid): ?Carbon
    {
        $response = $this->requestToMuck('getLastConnect', ['aid' => $aid]);
        if ($response > 0) return Carbon::createFromTimestamp($response);
        return null;
    }

    public function findAccountsByCharacterName(string $name): array
    {
        $response = $this->requestToMuck('findAccountsByCharacterName', ['name' => $name]);
        return $response ? explode(',', $response) : [];
    }

    /**
     * @inheritDoc
     */
    public function changeCharacterPassword(User $user, MuckCharacter $character, string $password): bool
    {
        $response = $this->requestToMuck('changeCharacterPassword', [
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
         * Expected format: dbref,creationTimestamp,"name",typeFlag,"metadata"
         * Name and metadata are enclosed in double quotes
         * Metadata varies depending on type:
         * Player - aid|level|avatar|colonSeparatedFlags
         * Zombie - level|avatar
         */
        $parts = str_getcsv($muckResponse, ',', '"', '\\');
        if (count($parts) != 5)
            throw new InvalidArgumentException("parseMuckObjectResponse: Response contains the wrong number of parts: $muckResponse");

        list($dbref, $creationTimestamp, $name, $typeFlag, $metadataAsString) = $parts;
        $dbref = intval($dbref);
        $creationTimestamp = Carbon::createFromTimestamp($creationTimestamp);
        $metadata = explode('|', $metadataAsString);
        switch ($typeFlag) {
            case 'p':
                if (count($metadata) != 4)
                    throw new InvalidArgumentException("parseMuckObjectResponse: Expected 4 items in metadata for a player: $metadataAsString");
                list($accountId, $level, $avatarString, $flagsAsString) = $metadata;
                $avatarInstance = resolve(AvatarService::class)->muckAvatarStringToAvatarInstance($avatarString);
                $flags = $flagsAsString ? explode(':', $flagsAsString) : [];
                $muckObject = new MuckCharacter($dbref, $name, $creationTimestamp,
                    $level, $avatarInstance, $flags, $accountId);
                break;
            case 'z':
                if (count($metadata) != 2)
                    throw new InvalidArgumentException("parseMuckObjectResponse: Expected 4 items in metadata for a player: $metadataAsString");
                list($level, $avatarString) = $metadata;
                $avatarInstance = resolve(AvatarService::class)->muckAvatarStringToAvatarInstance($avatarString);
                $muckObject = new MuckCharacter($dbref, $name, $creationTimestamp,
                    $level, $avatarInstance);
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
        $response = $this->requestToMuck('getByDbref', ['dbref' => $dbref]);
        if (!$response) return null;

        return $this->parseMuckObjectResponse($response);

    }

    /**
     * @inheritDoc
     */
    public function getByPlayerName(string $name): ?MuckCharacter
    {
        $response = $this->requestToMuck('getByPlayerName', ['name' => $name]);
        if (!$response) return null;

        return $this->parseMuckObjectResponse($response);
    }

    /**
     * @inheritDoc
     */
    public function getByApiToken(string $apiToken): ?MuckCharacter
    {
        $response = $this->requestToMuck('getByApiToken', ['api_token' => $apiToken]);
        if (!$response) return null;

        return $this->parseMuckObjectResponse($response);
    }

    /**
     * @inheritDoc
     */
    public function externalNotification(User $user, ?MuckCharacter $character, string $message): int
    {
        $count = $this->requestToMuck('externalNotification',
            ['aid' => $user->getAid(), 'character' => $character?->dbref(), 'message' => $message]);
        return (int)$count;
    }

    /**
     * @inheritDoc
     */
    public function avatarDollUsage(): array
    {
        return json_decode($this->requestToMuck('avatarDollUsage'), true);
    }

    /**
     * @inheritDoc
     */
    public function getAvatarOptionsFor(MuckCharacter $character, array $itemRequirements): array
    {
        return json_decode($this->requestToMuck('getAvatarOptions', [
            'character' => $character->dbref(), 'items' => $itemRequirements
        ]), true);
    }

    /**
     * @inheritDoc
     */
    public function saveAvatarCustomizations(MuckCharacter $character, array $colors, array $items): void
    {
        $this->requestToMuck('getAvatarOptionsFor', ['colors' => $colors, 'items' => $items]);
    }


}
