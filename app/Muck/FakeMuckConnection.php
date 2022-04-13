<?php


namespace App\Muck;

use App\Avatar\AvatarInstance;
use App\Avatar\AvatarService;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class FakeMuckConnection implements MuckConnection
{

    /** @var array<int, MuckDbref> */
    private array $fakeDatabaseByDbref;

    /** @var array<string, MuckCharacter> */
    private array $fakeDatabaseByPlayerName;

    public function __construct(array $config)
    {
        $fixedTime = Carbon::create(2000,1,1, 0, 0, 0 );
        AvatarInstance::default();
        $this->fakeDatabaseByDbref = [
            // Normal character
            1234 => new MuckCharacter(1234, 'TestCharacter', $fixedTime, 100, null, [], 1),
            // Character that grants the staff role
            2345 => new MuckCharacter(2345, 'StaffCharacter', $fixedTime, 14, null, ['staff'], 1),
            // Character that grants the admin role
            6789 => new MuckCharacter(6789, 'AdminCharacter', $fixedTime, 23, null, ['admin'], 1),
            // Unapproved character
            3456 => new MuckCharacter(3456, 'TestCharacter3', $fixedTime, 0, null, ['unapproved'], 1),
            // Unapproved character on other account
            4567 => new MuckCharacter(4567, 'TestCharacterA1', $fixedTime, 0, null, ['unapproved'], 6),
            // Approved character on admin account
            5678 => new MuckCharacter(5678, 'AdminAccountCharacter', $fixedTime, 0, null, ['admin'], 7)
        ];
        // For testing avatars - Won't work whilst running tests since the files aren't in the repo.
        try {
            $avatarService = resolve(AvatarService::class);
            // $avatarInstance = $avatarService->muckAvatarStringToAvatarInstance('ass=FS_Fox2;female=2;torso=FS_Fennec;eyes=Brown;female=8;hair=Silver;skin2=Silver;skin1=Greyscale');
            $avatarInstance = $avatarService->muckAvatarStringToAvatarInstance('ass=FS_Fox2;female=2;torso=FS_Fennec;eyes=Brown;female=8;hair=Silver;skin2=Silver;skin1=Greyscale;item=foxplush/0/0/-2/0.8/90;item=foxplush/150/270/15/0.4/0;item=foxplush/150/270/16/0.4/30;item=foxplush/150/270/16/0.4/60;item=foxplush/150/270/16/0.4/90;item=ruinedcity/0/0/-3/1.0/0');
            // $avatarInstance = $avatarService->muckAvatarStringToAvatarInstance('ass=FS_Fox2;female=2;torso=FS_Fennec;eyes=Brown;female=8;hair=Silver;skin2=Silver;skin1=Greyscale;item=foxplush/50/100/16/0.8/0;item=foxplush/50/100/16/0.8/45');
            $this->fakeDatabaseByDbref[4321] = new MuckCharacter(4321, 'AvatarCharacter', $fixedTime, 1, $avatarInstance, [], 7);
        }
        catch (\Exception $e)
        {
            Log::debug("Couldn't load an avatar. This is only an issue in local development and fine during automatic testing.");
        }

        foreach ($this->fakeDatabaseByDbref as $entry) {
            if ($entry->typeFlag() == 'p') $this->fakeDatabaseByPlayerName[strtolower($entry->name())] = $entry;
        }
    }

    /**
     * Just a method to provide unified logging
     * @param string $call
     * @param array $data
     */
    private static function fakeMuckCall(string $call, array $data = [])
    {
        $dataAsString = json_encode($data);
        Log::debug("FakeMuckCall - $call, data: $dataAsString");
    }

    #region Auth Requests

    /**
     * @inheritDoc
     */
    public function validateCredentials(MuckCharacter $character, array $credentials): bool
    {
        self::fakeMuckCall('validateCredentials', $credentials);
        if ($character->dbref() == 1234 && $credentials['password'] == 'password') return true;
        if ($character->dbref() == 1234 && $credentials['password'] == 'password2') return true;
        return false;
    }

    #endregion Auth Requests

    /**
     * @inheritDoc
     */
    public function getCharactersOf(User $user): array
    {
        self::fakeMuckCall('getCharactersOf', ['aid' => $user->getAid()]);
        $result = [];
        foreach ($this->fakeDatabaseByDbref as $character) {
            if ($character->aid() == $user->getAid()) $result[$character->dbref()] = $character;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getCharacterSlotState(User $user): array
    {
        self::fakeMuckCall('getCharacterSlotState');
        return [
            "characterSlotCount" => 2,
            "characterSlotCost" => 50
        ];
    }

    /**
     * @inheritDoc
     */
    public function buyCharacterSlot(User $user): array
    {
        self::fakeMuckCall('buyCharacterSlot');
        return [
            "characterSlotCount" => 4,
            "characterSlotCost" => 60
        ];
    }

    /**
     * @inheritDoc
     */
    public function findProblemsWithCharacterName(string $name): string
    {
        self::fakeMuckCall('getAnyIssuesWithCharacterName', ['name' => $name]);
        if (strtolower($name) == 'test') return 'That name is a test.';
        if (str_contains($name, ' ')) return 'That name contains a space.';
        return '';
    }

    /**
     * @inheritDoc
     */
    public function findProblemsWithCharacterPassword(string $password): string
    {
        self::fakeMuckCall('getAnyIssuesWithCharacterPassword', ['password' => $password]);
        if (strtolower($password) == 'test') return 'That password is a test.';
        return '';
    }

    /**
     * @inheritDoc
     */
    public function createCharacterForUser(string $name, User $user): array
    {
        self::fakeMuckCall('createCharacter', ['name' => $name, 'aid' => $user->getAid()]);
        return [
            "character" => new MuckCharacter(4657, 'FakeCharacter', Carbon::now(), $user->getAid()),
            "initialPassword" => 'test'
        ];
    }

    /**
     * @inheritDoc
     */
    public function finalizeCharacter(array $characterData): array
    {
        if ($characterData['flaws'] && in_array('Unselectable Flaw', $characterData['flaws']))
            return ['success' => false, 'messages' => ['The unselectable flaw was selected.', 'Second line test.']];
        return ['success' => true];
    }

    public function getCharacterInitialSetupConfiguration(User $user): array
    {
        return [
            "factions" => [
                "FakeFaction1" => [
                    "description" => "The first fake faction for testing."
                ],
                "FakeFaction2" => [
                    "description" => "The second fake faction for testing.<br/>This one has a line break in it."
                ],
                "Longer named faction 3" => [
                    "description" => "The third faction with some differences so it's actually possible to check scaling. Along with some extra text to effectively act as a second line."
                ]
            ],
            "flaws" => [
                "FakeFlaw1" => [
                    "description" => "The first fake flaw for testing.",
                    "excludes" => []
                ],
                "FakeFlaw2" => [
                    "description" => "The second fake flaw for testing.",
                    "excludes" => ["FakeFlaw3"]
                ],
                "FakeFlaw2bOrNot2b" => [
                    "description" => "Somewhere between the second and third flaw, complete with a terrible pun in the name.",
                    "excludes" => ["FakeFlaw3"]
                ],
                "FakeFlaw3" => [
                    "description" => "The third fake flaw for testing.",
                    "excludes" => ["FakeFlaw2"]
                ],
                "Unselectable Flaw" => [
                    "description" => "Picking this should cause validation to fail.",
                    "excludes" => []
                ]
            ],
            "perks" => [
                "FakePerk1" => [
                    "description" => "The first fake perk for testing.",
                    "category" => 'appearance',
                    "excludes" => []
                ],
                "FakePerk2" => [
                    "description" => "The second fake perk for testing.",
                    "category" => 'appearance',
                    "excludes" => ["FakePerk3"]
                ],
                "FakePerk3" => [
                    "description" => "The third fake perk for testing.",
                    "category" => 'appearance',
                    "excludes" => ["FakePerk2"]
                ],
                "Fake Perk with Lorem Ipsum" => [
                    "description" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec faucibus porta dui, vel porta leo consectetur vel. Sed a nisl ligula. Donec sed nisi et magna commodo euismod id et dolor. Aliquam sed sapien quis est semper tempus. Curabitur nec lacus sit amet massa sodales accumsan ut eget urna. Vivamus justo felis, convallis et sapien id, dapibus aliquam mauris. Cras sit amet nulla eu odio ultrices congue sed non ipsum. Phasellus ut lacinia arcu, quis volutpat justo. Proin aliquet, dui et euismod cursus, ligula metus fringilla orci, nec mattis sem nunc a dui. Phasellus a velit quis nisi auctor pharetra. Integer lacus libero, consequat congue egestas vel, finibus id leo. Duis velit nulla, scelerisque id justo in, dignissim mollis dui. ",
                    "category" => 'appearance',
                    "excludes" => ["FakePerk2"]
                ],
                "Fake Perk in a different category" => [
                    "description" => "As noted",
                    "category" => 'history',
                    "excludes" => ["FakePerk2"]
                ]
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function usdToAccountCurrency(float $usdAmount): ?int
    {
        self::fakeMuckCall('usdToAccountCurrency', ['usdAmount' => $usdAmount]);
        return $usdAmount * 3;
    }

    /**
     * @inheritDoc
     */
    public function fulfillAccountCurrencyPurchase(int $accountId, float $usdAmount,
                                                   int $accountCurrency, ?string $subscriptionId): int
    {
        self::fakeMuckCall('fulfillAccountCurrencyPurchase', [
            'accountId' => $accountId,
            'usdAmount' => $usdAmount,
            'accountCurrency' => $accountCurrency,
            'subscriptionId' => $subscriptionId
        ]);
        return $accountCurrency;
    }

    /**
     * @inheritDoc
     */
    public function fulfillPatreonSupport(int $accountId, int $accountCurrency): int
    {
        self::fakeMuckCall('fulfillPatreonSupport', [
            'accountId' => $accountId,
            'accountCurrency' => $accountCurrency
        ]);
        return $accountCurrency;
    }

    /**
     * @inheritDoc
     */
    public function rewardItem(int $accountId, float $usdAmount, int $accountCurrency, string $itemCode): int
    {
        self::fakeMuckCall('rewardItem', [
            'accountId' => $accountId,
            'usdAmount' => $usdAmount,
            'accountCurrency' => $accountCurrency,
            'itemCode' => $itemCode
        ]);
        return $accountCurrency;
    }

    /**
     * @inheritDoc
     */
    public function stretchGoals(): array
    {
        self::fakeMuckCall('stretchGoals');
        return [
            'progress' => 100,
            'goals' => [ // Keys can't be int in JSON, so also need to be strings here
                '50' => 'This example goal has been achieved',
                '200' => 'This example goal has not been achieved',
                '5000' => 'This example goal has also not been achieved but is longer to test formatting.'
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getLastConnect(int $aid): ?Carbon
    {
        self::fakeMuckCall('getLastConnect', ['aid' => $aid]);
        return Carbon::now();
    }

    /**
     * @inheritDoc
     */
    public function findAccountsByCharacterName(string $name): array
    {
        self::fakeMuckCall('findAccountsByCharacterName', ['name' => $name]);
        if ($name == 'test') return ['TestCharacter' => 1];
        return [];
    }

    /**
     * @inheritDoc
     */
    public function changeCharacterPassword(User $user, MuckCharacter $character, string $password): bool
    {
        self::fakeMuckCall('changeCharacterPassword', [
            'aid' => $user->getAid(),
            'dbref' => $character->dbref(),
            'password' => $password
        ]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getByDbref(int $dbref): ?MuckDbref
    {
        self::fakeMuckCall('getByDbref', ['dbref' => $dbref]);
        $object = null;
        if (array_key_exists($dbref, $this->fakeDatabaseByDbref)) $object = $this->fakeDatabaseByDbref[$dbref];
        Log::debug("FakeMuckCall - getByDbref result: " . $object);
        return $object;
    }

    /**
     * @inheritDoc
     */
    public function getByPlayerName(string $name): ?MuckCharacter
    {
        self::fakeMuckCall('getByPlayerName', ['name' => $name]);
        $nameLowerCase = strtolower($name);
        if (array_key_exists($nameLowerCase, $this->fakeDatabaseByPlayerName))
            return $this->fakeDatabaseByPlayerName[$nameLowerCase];
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getByApiToken(string $apiToken): ?MuckCharacter
    {
        self::fakeMuckCall('getByApiToken', ['api_token' => $apiToken]);
        if ($apiToken == 'token_testcharacter') return $this->fakeDatabaseByDbref[1234];
        return null;
    }

    /**
     * @inheritDoc
     */
    public function externalNotification(User $user, ?MuckCharacter $character, string $message): int
    {
        self::fakeMuckCall('externalNotification',
            ['aid' => $user->getAid(), 'character' => $character?->dbref(), 'message' => $message]);
        if ($character && array_key_exists($character->dbref(), $this->fakeDatabaseByDbref)) return 1; else return 0;
    }

    /**
     * @inheritDoc
     */
    public function avatarDollUsage(): array
    {
        self::fakeMuckCall('avatarDollUsage');
        return [
            'FS_Badger' => ['BadgerInfection1', 'BadgerInfection2'],
            'FS_Bear' => ['NotABear'],
            'NonExistent' => ['Broken']
        ];
    }

    /**
     * @inheritDoc
     */
    public function bootAvatarEditor(MuckCharacter $character, array $itemRequirements): array
    {
        self::fakeMuckCall('bootAvatarEditor',
            ['character' => $character->dbref(), 'requirements' => $itemRequirements]);
        return [
            'gradients' => [
                'Blonde'
            ],
            'items' => [
                'antennae02' => 1,
                'ascot' => 2,
                'assault_rifle' => 3
            ]
        ];
    }
}
