<?php

// This class extends the PayPal SDK to provide access to subscription functionality.
// One day it should be replaced with whatever the SDK provides

// Built using documentation at https://developer.paypal.com/docs/api/subscriptions/v1/

namespace App\Payment\PayPalRequests;

use PayPalHttp\HttpRequest;

class WebhooksList extends HttpRequest
{
    function __construct()
    {
        parent::__construct("/v1/notifications/webhooks?", "GET");

        // $this->path = str_replace("{order_id}", urlencode($orderId), $this->path);
        $this->headers["Content-Type"] = "application/json";
    }



}
