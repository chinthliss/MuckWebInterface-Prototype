<?php

// This class extends the PayPal SDK to provide access to subscription functionality.
// One day it should be replaced with whatever the SDK provides

// Built using documentation at https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature

namespace App\Payment\PayPal;

use PayPalHttp\HttpRequest;

class WebhooksVerifySignature extends HttpRequest
{
    function __construct()
    {
        parent::__construct("/v1/notifications/verify-webhook-signature", "POST");

        $this->headers["Content-Type"] = "application/json";
    }



}
