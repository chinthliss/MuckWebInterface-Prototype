<?php

namespace App\Console\Commands;

use App\Payment\PatreonManager;
use Illuminate\Console\Command;

class UpdatePatreonDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patreon:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs an update from Patreon';

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
        $this->info("Updating from Patreon, this may take some time.");
        $patreonManager->updateFromPatreon();
        $this->info("Complete.");
    }
}
