<?php

namespace App\Console\Commands;

use App\Payment\PayPalManager;
use Illuminate\Console\Command;

class ListPayPalProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypal:listproducts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists the PayPal Products';

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
        $products = $payPalManager->getProducts();
        var_dump ($products);
    }
}
