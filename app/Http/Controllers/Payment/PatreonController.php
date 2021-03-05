<?php


namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Payment\PatreonManager;

class PatreonController extends Controller
{
    public function adminShow()
    {
        return view('admin.patrons')->with([
            'apiUrl' => route('admin.patrons.api')
        ]);
    }

    public function adminGetPatrons(PatreonManager $patreonManager)
    {
        $patrons = $patreonManager->getPatrons();
        $response = [];
        foreach ($patrons as $patron) {
            $patronArray = $patron->toAdminArray();
            //For the purpose of this interface we don't include followers
            if ($patronArray['totalSupportUsd'] == 0) continue;
            $user = $patreonManager->userForPatron($patron);
            if ($user) {
                $patronArray['accountId'] = $user->getAid();
                $patronArray['account_url'] = route('admin.account', ['accountId' => $user->getAid()]);
            }
            array_push($response, $patronArray);
        }
        return $response;
    }


}
