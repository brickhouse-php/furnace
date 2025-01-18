<?php

namespace Brickhouse\Furnace;

use Brickhouse\Log\Log;

class Pipeline
{
    public function __construct(
        public readonly AssetConfig $config,
    ) {}

    /**
     * Determines the installation path for ESBuild.
     *
     * @return string
     */
    protected function esbuildInstallPath(): string
    {
        return base_path("node_modules", "esbuild", "bin", "esbuild");
    }

    /**
     * Determines whether ESBuild is already installed via some Node package manager.
     *
     * @return boolean
     */
    protected function hasEsbuildInstalled(): bool
    {
        return @is_file($this->esbuildInstallPath());
    }

    /**
     * Builds the assets with Esbuild.
     *
     * @return integer
     */
    public function build(): int
    {
        if (!$this->hasEsbuildInstalled()) {
            Log::error("Esbuild is not currently installed.");
            Log::error("Please install it via npm, yarn or some other JS package manager.");

            return 1;
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $command = $this->getBuildScript();

        Log::info('Building assets with esbuild...');
        Log::debug('{command}', ['command' => join(" ", $command)]);

        if (($process = proc_open($command, $descriptors, $pipes, $this->config->root)) === false) {
            throw new \RuntimeException("Failed to build with Esbuild: " . (error_get_last()['message'] ?? ''));
        }

        if (trim($stdout = stream_get_contents($pipes[1])) !== '') {
            Log::info($stdout);
        }

        if (trim($stderr = stream_get_contents($pipes[2])) !== '') {
            Log::info($stderr);
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }

    /**
     * Determines which build script to use.
     * If a build script is defined at `assets/build.mjs`, that script is used. Otherwise, uses the built-in fallback.
     *
     * @return string|list<string>
     */
    protected function getBuildScript(): string|array
    {
        $customBuildScript = path($this->config->assets, "build.mjs");
        if (@is_file($customBuildScript)) {
            return ['node', $customBuildScript];
        }

        return [
            $this->esbuildInstallPath(),
            "{$this->config->assets}/app.ts",
            "{$this->config->assets}/app.js",
            "--bundle",
            "--minify",
            "--color=true",
            "--outdir={$this->config->output}",
            "--log-override:empty-glob=silent",
        ];
    }
}
