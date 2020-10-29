<?php

// This class extends the PayPal SDK to provide access to subscription functionality.
// One day it should be replaced with whatever the SDK provides

// Built using documentation at https://developer.paypal.com/docs/api/subscriptions/v1/

namespace App\Payment\PayPalRequests;

use PayPalHttp\HttpRequest;

class SubscriptionsCreateSubscription extends HttpRequest
{
    function __construct()
    {
        parent::__construct("/v1/billing/subscriptions", "POST");

        $this->headers["Content-Type"] = "application/json";
    }

    public function prefer($prefer)
    {
        $this->headers["Prefer"] = $prefer;
    }
}
