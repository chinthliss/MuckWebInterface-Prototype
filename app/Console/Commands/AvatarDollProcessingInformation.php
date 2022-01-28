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
        $this->comment("Looking up layer information for doll $dollName");

        dd($avatarService->getDollDefaultGradientInformation($dollName));
        $this->info("Gradients");
        foreach ($avatarService->getDollDefaultGradientInformation($dollName) as $gradient) {
            $this->line($gradient->name . "(" . count($gradient->steps) . " steps)");
        }

        $this->info("Drawing order");
        foreach ($avatarService->getDollLayerDrawingOrder($dollName) as $subpart => $steps) {
            $this->line(str_pad($subpart, 10, ' ', STR_PAD_LEFT) . '>' . json_encode($steps));
        }
    }
}
