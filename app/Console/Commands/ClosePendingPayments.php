<?php

namespace App\Console\Commands;

use App\Payment\PaymentSubscriptionManager;
use App\Payment\PaymentTransactionManager;
use Illuminate\Console\Command;

class ClosePendingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:closepending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Closes payment related things that were never accepted by the user';

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
                           PaymentSubscriptionManager  $subcriptionManager)
    {
        $transactionManager->closePending();
        $subcriptionManager->closePending();
    }
}
