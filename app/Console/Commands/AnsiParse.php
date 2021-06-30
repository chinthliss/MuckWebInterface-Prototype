<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\Ansi;

class AnsiParse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ansi:parse {string}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parses an ansi string. Intended for testing.';

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
    public function handle()
    {
        $unparsedString = $this->argument('string');
        $this->comment('String before:');
        $this->info($unparsedString);

        $parsedString = Ansi::unparsedToHtml($unparsedString);
        $this->comment('String after:');
        $this->info($parsedString);

    }
}
