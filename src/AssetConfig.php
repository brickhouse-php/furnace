<?php

namespace Brickhouse\Furnace;

use Brickhouse\Config\Config;

class AssetConfig extends Config
{
    /**
     * Gets the absolute path to the root directory.
     *
     * @var string
     */
    public readonly string $root;

    /**
     * Gets the absolute path to the directory with assets to compile.
     *
     * @var string
     */
    public readonly string $assets;

    /**
     * Gets the absolute path to the directory where compiled assets should be stored.
     *
     * @var string
     */
    public readonly string $output;

    public function __construct(
        null|string $root = null,
        string $assets = "assets",
        string $output = "public/_build",
    ) {
        $this->root = $root ?? base_path();

        $this->assets = path($this->root, $assets);
        $this->output = path($this->root, $output);
    }
}
