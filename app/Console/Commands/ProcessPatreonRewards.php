<?php

namespace App\Console\Commands;

use App\Payment\PatreonManager;
use Illuminate\Console\Command;

class ProcessPatreonRewards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patreon:processrewards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes patreon details and does rewards';

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
    public function handle(PatreonManager $patreonManager)
    {
        $this->info("Processing Patreon rewards");
        $patreonManager->processRewards();
        $this->info("Complete.");
    }
}
