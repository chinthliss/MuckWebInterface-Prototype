<?php

namespace App\Console\Commands;

use App\Payment\PatreonManager;
use Illuminate\Console\Command;

class ConvertLegacyPatreonClaims extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patreon:convertlegacy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Converts old patreon_claims entries into billing_transactions';

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
        $pledges = $patreonManager->getPatrons();
        $legacyclaims = $patreonManager->getLegacyClaims();

        foreach ($legacyclaims as $claim) {

        }
    }
}
