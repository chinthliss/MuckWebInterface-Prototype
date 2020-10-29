<?php

namespace App\Console\Commands;

use App\Payment\PayPalManager;
use Illuminate\Console\Command;

class ConfigurePayPal extends Command
{
    private $dayFrequencies = [7, 14, 30, 60, 90, 120, 150, 180, 360];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypal:config {--fix} {host?}';

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
                $this->info("Product exists");
            } else {
                $this->error("Product doesn't exist");
                if ($updateConfiguration) {
                    $createdProductId = $payPalManager->createProduct($productId,
                        "Subscription for " . config('app.name'));
                    if ($createdProductId)
                        $this->info("Product created.");
                    else
                        $this->error("Product creation failed.");
                }
            }

            $this->comment(PHP_EOL . 'Checking subscription plans');
            foreach ($this->dayFrequencies as $days) {
                $plan = $payPalManager->getSubscriptionPlan($days);
                if ($plan)
                    $this->info('  ' . $days . ' days plan exists - ' . $plan);
                else {
                    $this->error('  ' . $days . ' days plan doesn\'t exist.');
                    if ($updateConfiguration) {
                        $createdPlanId = $payPalManager->createSubscriptionPlan($days);
                        if ($createdPlanId)
                            $this->info("    Plan created.");
                        else
                            $this->error("    Product creation failed.");
                    }
                }
            }

            $this->comment(PHP_EOL . 'Checking Webhooks');
            $host = rtrim(str_replace('host=', '', $this->argument('host')), '/');
            $detectedHost = request()->getSchemeAndHttpHost();
            if (!$host) {
                $this->warn("  Since it can't be detected 100% accurately from a console command, you need to specify a host for complete checking/fixing. Use the optional argument 'host=<host>'.");
                $this->warn("  In case it's correct, you can copy/paste the detected host: " . $detectedHost);
            }
            $webhook = null;
            foreach ($payPalManager->getWebhooks() as $possibleWebhook) {
                if (strpos($possibleWebhook->url, '/accountcurrency/paypal_webhook') === false) continue;
                if ($host && strpos($possibleWebhook->url, $host) === false) continue;

                $webhook = $possibleWebhook;
            }
            if ($webhook) {
                $this->info('  Valid webhook exists using: ' . $webhook->url);
                if (!$host) $this->warn("Unable to validate the host without it being specified.");
            } else {
                $this->error('  No valid webhook exists.');
                if ($updateConfiguration) {
                    if (!$host) $this->warn("Unable to fix without a specified host.");
                    else {
                        $this->info('  Creating a webhook with the host ' . $host);
                        $webhookId = $payPalManager->createWebhook($host . '/accountcurrency/paypal_webhook', [
                            'PAYMENT.SALE.COMPLETED', // Payment received
                            'BILLING.SUBSCRIPTION.CREATED',
                            'BILLING.SUBSCRIPTION.ACTIVATED',
                            'BILLING.SUBSCRIPTION.UPDATED',
                            'BILLING.SUBSCRIPTION.EXPIRED',
                            'BILLING.SUBSCRIPTION.CANCELLED',
                            'BILLING.SUBSCRIPTION.SUSPENDED',
                            'BILLING.SUBSCRIPTION.PAYMENT.FAILED'
                        ]);
                        $this->info('  Webhook created with ID: ' . $webhookId);
                    }
                }
            }
        }

    }
}
