<?php


namespace App\Payment;

use Illuminate\Support\Carbon;

class Card
{
    public $id;
    public $cardType;
    public $cardNumber;

    /**
     * @var Carbon|null $expiryDate
     */
    public $expiryDate;

    public $isDefault;

    /*
     * @var int[]
     */
    public $subscriptions = [];

    public function maskedCardNumber()
    {
        return '..' . substr($this->cardNumber, -4);
    }

    //Sanitised version of this object, for passing out to client interfaces
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'cardType' => $this->cardType,
            'maskedCardNumber' => $this->maskedCardNumber(),
            'expiryDate' => $this->expiryDate->format('m/Y'),
            'isDefault' => $this->isDefault
        );
    }

    #region Class functionality
    const CARD_TYPE_MATCHES = [
        "VISA" => '/^4[0-9]{12}(?:[0-9]{3})?$/',
        "American Express" => '/^3[47][0-9]{13}$/',
        "JCB" => '/^(?:2131|1800|35\d{3})\d{11}$/',
        // "Discover" => '/^(?:6011\d{12})|(?:65\d{14})$/', // Not accepted by us
        "Mastercard" => '/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$/'
        //Solo, Switch removed from this list due to being discontinued. Maestro removed as not actually accepted by Authorize.net
    ];

    /**
     * @param string|int $number
     * @return bool
     */
    public static function checkLuhnChecksumIsValid($number)
    {
        $total = 0;
        foreach (str_split(strrev(strval($number))) as $index => $character) {
            $total += ($index % 2 == 0 ? $character : array_sum(str_split(strval($character * 2))));
        }
        return ($total % 10 == 0);
    }

    //Returns blank array if everything is okay, otherwise returns errors in the form { <element>:"error" }
    public static function findIssuesWithAddCardParameters($cardNumber, $expiryDate, $securityCode)
    {
        $errors = [];

        //Card Number checks
        $cardNumber = str_replace([' ', '-'], '', $cardNumber);
        if ($cardNumber == '')
            $errors['cardNumber'] = 'Card number is required.';
        else {
            if (!is_numeric($cardNumber)) $errors['cardNumber'] = 'Card number can only contain numbers.';
            else {
                $cardType = "";
                foreach (self::CARD_TYPE_MATCHES as $testingFor => $cardTypeTest) {
                    if (preg_match($cardTypeTest, $cardNumber)) $cardType = $testingFor;
                }
                if (!$cardType) $errors['cardNumber'] = 'Unrecognized card number.';
                else {
                    if (!self::checkLuhnChecksumIsValid($cardNumber)) $errors['cardNumber'] = 'Invalid card number.';
                }
            }
        }

        //Expiry Date checks
        if (!preg_match('/^\d\d\/\d\d\d\d$/', $expiryDate)) {
            $errors['expiryDate'] = 'Expiry Date must be in the form MM/YYYY.';
        } else {
            [$month, $year] = explode('/', $expiryDate);

            $endDate = Carbon::createFromDate($year, $month + 1, 1)->startOfDay();;
            if ($endDate < Carbon::now()) {
                $errors['expiryDate'] = 'Card has expired.';
            }
        }

        //Security Code checks
        if ($securityCode == '')
            $errors['securityCode'] = 'Security code is required.';
        else {
            if (!is_numeric($securityCode))
                $errors['securityCode'] = 'Security code can only contain numbers.';
            else if (strlen($securityCode) < 3 or strlen($securityCode) > 4)
                $errors['securityCode'] = 'Security code must be 3 or 4 numbers long.';
        }

        return $errors;
    }
    #endregion Class functionality
}
