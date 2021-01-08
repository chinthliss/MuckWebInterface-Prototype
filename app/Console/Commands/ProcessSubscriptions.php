<?php

namespace App\Console\Commands;

use App\Payment\PaymentSubscriptionManager;
use App\Payment\PaymentTransactionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:processsubscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charges active subscriptions that are due payment';

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
    public function handle(PaymentTransactionManager $transactionManager,
                           PaymentSubscriptionManager $subscriptionManager)
    {
        Log::debug('Processing subscriptions due payment starts');
        $subscriptions = $subscriptionManager->getSubscriptionsDuePayment();
        foreach ($subscriptions as $subscription) {
            if ($subscription->vendor == 'paypal') continue; // Done externally.

            // Sanity check to avoid re-charging too soon
            if ($subscription->lastChargeAt
                && $subscription->lastChargeAt->copy()->addDays($subscription->recurringInterval) > Carbon::now()) {
                Log::warning("Skipping payment processing on subscription " . $subscription->id
                    . ", because its last charge date (" . $subscription->lastChargeAt
                    . ") is too recent. Next charge date possibly set incorrectly.");
                continue;
            }

            Log::info('Processing subscription payment for ' . $subscription->id
                . ' which has a last payment date of: ' . ($subscription->lastChargeAt ?? 'None'));

            Log::warning('Payment subscription processing not implemented yet.');
        }
        Log::debug('Processing subscriptions due payment finished');
    }
}
