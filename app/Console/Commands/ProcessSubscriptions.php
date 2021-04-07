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
    public function handle(PaymentSubscriptionManager $subscriptionManager)
    {
        $subscriptionManager->processSubscriptions();
    }
}
