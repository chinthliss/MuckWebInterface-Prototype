<?php

// This class extends the PayPal SDK to provide access to subscription functionality.
// One day it should be replaced with whatever the SDK provides

// Built using documentation at https://developer.paypal.com/docs/api/webhooks/v1/

namespace App\Payment\PayPalRequests;

use PayPalHttp\HttpRequest;

class WebhooksCreate extends HttpRequest
{
    function __construct()
    {
        parent::__construct("/v1/notifications/webhooks", "POST");

        $this->headers["Content-Type"] = "application/json";
    }



}
