<?php

namespace Brickhouse\Furnace\Commands;

use Brickhouse\Console\Command;
use Brickhouse\Furnace\Pipeline;

class Build extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    public string $name = 'build';

    /**
     * The description of the console command.
     *
     * @var string
     */
    public string $description = 'Builds assets for the application.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pipeline = resolve(Pipeline::class);

        return $pipeline->build();
    }
}
