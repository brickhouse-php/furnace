<?php

namespace Brickhouse\Furnace;

use Brickhouse\Furnace\Commands;

class Extension extends \Brickhouse\Core\Extension
{
    /**
     * Gets the human-readable name of the extension.
     */
    public string $name = "brickhouse/furnace";

    /**
     * Invoked before the application has started.
     */
    public function register(): void
    {
        //
    }

    /**
     * Invoked after the application has started.
     */
    public function boot(): void
    {
        $this->addCommands([
            Commands\Build::class,
            Commands\Install::class,
        ]);
    }
}
