<?php

// This class extends the PayPal SDK to provide access to subscription functionality.
// One day it should be replaced with whatever the SDK provides

// Built using documentation at https://developer.paypal.com/docs/api/subscriptions/v1/

namespace App\Payment\PayPalRequests;

use PayPalHttp\HttpRequest;

class SubscriptionsCancelSubscription extends HttpRequest
{
    function __construct($subscriptionId)
    {
        parent::__construct("/v1/billing/subscriptions/{subscription_id}/cancel", "POST");

        $this->path = str_replace("{subscription_id}", urlencode($subscriptionId), $this->path);
        $this->headers["Content-Type"] = "application/json";
    }

    public function prefer($prefer)
    {
        $this->headers["Prefer"] = $prefer;
    }
}
