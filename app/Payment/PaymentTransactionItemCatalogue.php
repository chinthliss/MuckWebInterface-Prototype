<?php


namespace App\Payment;


use App\User;
use Exception;

class PaymentTransactionItemCatalogue
{
    private const itemsCatalogue = [
        "FLEXPACK" => [
            "name" => "Survival Pack",
            "description" => "On top of gaining mako for the amount spent, all your current characters gain a large nano pack and a monster bait. Note: Future characters do not gain these items. Items are not exchangeable. Loss of item does not entitle to replacement. (You can also gift this item to other people, your account recieves the mako, but you also recieve a box of goodies. Handy if you've already recieved the pack on your alts before! Any of your alts that have not received this pack, you could use the item on them as well. Check your notices on which character the pack ends up on.)",
            "priceUsd" => 30.00
        ],
        "FLEXACCESSORY" => [
            "name" => "Custom Accessory",
            "description" => "Redeem this with staff to get a new accessory drawn up. Your account will have permanent access to this accessory. At your option, the item can be locked for up to six months before release to the public.",
            "priceUsd" => 10.00
        ],
        "FLEXADEPT" => [
            "name" => "Nanite Adept Care Package",
            "description" => "Just the thing for the new nanite adept(Dedication not included)! Includes many popular add ons and accessories at a discounted group price. One character will receive: Nanite Carnal Equalizer, Nanite Sexual Stabilizer, Nanite Gender Assigner, Reconstructor, Nanite Fine Tuner, Nanite Spatial Recalculator, Nanite Resistor, Nanite Social Devolutionizer, and the Nanite Focus Device. All items are tradeable, so if you already have one or two, you can give it to a friend.",
            "priceUsd" => 50.00
        ],
        "FLEXAVATAR" => [
            "name" => "Species Avatar",
            "description" => "Get a new avatar made up of a species missing one. * FINE PRINT: We reserve the right to re use the avatar for related species.",
            "priceUsd" => 75.00
        ],
        "FLEXPERMDED" => [
            "name" => "Permanent Dedication",
            "description" => "Keep a dedication forever. Use this item in the dedication room that you have already purchased and your account will be flagged to own the dedication. Then any future alt that tries to dedicate will do so for free.",
            "priceUsd" => 10.00
        ],
        "FLEXPIC" => [
            "name" => "Character Portrait",
            "description" => "Get a picture drawn of your character, to be used on your profile and anywhere you like. * FINE PRINT: We reserve the right to use the picture in promotion of the game.",
            "priceUsd" => 40.00
        ],
        "FLEXSUB1" => [
            "name" => "Hiker Tag",
            "description" => "You will receive one hiker tag. When used, you gain permanent hiker subscription level, normally $60+ per year, yours forever.",
            "priceUsd" => 50.00
        ],
        "FLEXSUB2" => [
            "name" => "Survivalist Tag",
            "description" => "You will receive one Survivalist tag. When used, you gain permanent survivalist subscription level, normally $180 per year, yours forever.",
            "priceUsd" => 150.00
        ],
        "FLEXSUB3" => [
            "name" => "Wasteland Warrior Tag",
            "description" => "You will receive one Wasteland Warrior tag. When used, you gain permanent wasteland warrior subscription level, normally $360 per year, yours forever.",
            "priceUsd" => 300.00
        ],
    ];

    /**
     * Simple function to add individual tests to - until something more elaborate is needed
     */
    public function isEligibleFor(User $user, string $itemCode): bool
    {
        return true;
    }

    public function getEligibleItemsFor(User $user): array
    {
        $items = [];
        foreach ($this::itemsCatalogue as $code => $item)
        {
            if ($this->isEligibleFor($user, $code)) {
                array_push($items, $code);
            }
        }
        return $items;
    }

    public function itemCodeToArray(string $itemCode)
    {
        if (!array_key_exists($itemCode, $this::itemsCatalogue))
            throw new Exception("Invalid item code - " . $itemCode);

        $item = $this::itemsCatalogue[$itemCode];
        return [
            "code" => $itemCode,
            "name" => $item['name'],
            "description" => $item['description'],
            "priceUsd" => $item['priceUsd']
        ];
    }
}
