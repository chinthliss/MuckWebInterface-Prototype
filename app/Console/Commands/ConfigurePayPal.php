<?php

namespace App\Console\Commands;

use App\Payment\PayPal\PayPalManager;
use Illuminate\Console\Command;

class ConfigurePayPal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypal:config {--fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the paypal configuration or updates it as required';

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
        $updateConfiguration = $this->option('fix');

        if ($updateConfiguration)
            $this->comment('Checking configuration and updating as required.');
        else
            $this->comment('Checking configuration. No updates will be done (use --fix to update).');

        $this->line('');
        $this->comment('Product ID');
        $productId = config('services.paypal.subscriptionId');
        if (!$productId) {
            $this->error("No product ID is set. This can't be fixed by the program, ensure you've set the desired product ID in .env");
        } else {
            $this->info('Product ID is ' . $productId);

            $this->comment(PHP_EOL . 'Checking product');
            $products = $payPalManager->getProducts();
            if (array_key_exists($productId, $products)) {
                $this->info("Product configured correctly");
            } else {
                $this->error("Product isn't configured.");
                if ($updateConfiguration) {
                    $this->error("TBC.");
                }
            }

            $this->comment(PHP_EOL . 'Checking subscription plans');
            //TODO : need to set what this should look like
            $this->info('Subscription Plans');

            $this->comment(PHP_EOL . 'Checking Webhooks');
            $this->info('Webhooks');
        }

    }
}
