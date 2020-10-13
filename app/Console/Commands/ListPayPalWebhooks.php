<?php

namespace App\Console\Commands;

use App\Payment\PayPalRequests\PayPalManager;
use Illuminate\Console\Command;

class ListPayPalWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypal:listwebhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists the PayPal Webhooks';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(PayPalManager $payPalManager)
    {
        $webHooks = $payPalManager->getWebhooks();
        var_dump ($webHooks);
    }
}
