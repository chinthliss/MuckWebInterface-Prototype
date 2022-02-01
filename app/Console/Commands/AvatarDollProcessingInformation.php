<?php

namespace App\Console\Commands;

use App\Avatar\AvatarService;
use Illuminate\Console\Command;

class AvatarDollProcessingInformation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avatar:info {doll}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Looks up layer information for an avatar doll. Intended for testing.';

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
    public function handle(AvatarService $avatarService)
    {
        $dollName = $this->argument('doll');
        $this->comment("Looking up information for doll $dollName");

        $doll = $avatarService->getDoll($dollName);
        $this->info("Default gradients");
        foreach ($doll->defaultGradients as $index => $gradient) {
            $this->line($index . ": " . $gradient->name . "(" . count($gradient->steps) . " steps)");
        }

        $this->info("Drawing order");
        foreach ($doll->drawingInformation as $subpart => $steps) {
            $this->line(str_pad($subpart, 10, ' ', STR_PAD_LEFT) . '>' . json_encode($steps));
        }
    }
}
