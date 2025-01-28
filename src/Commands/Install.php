<?php

namespace Brickhouse\Furnace\Commands;

use Brickhouse\Console\Attributes\Option;
use Brickhouse\Console\Command;
use Brickhouse\Console\InputOption;

use function \Brickhouse\Console\Prompts\confirm;
use function \Brickhouse\Console\Prompts\multiselect;

class Install extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    public string $name = 'install:furnace';

    /**
     * The description of the console command.
     *
     * @var string
     */
    public string $description = 'Installs Furnace into the current application.';

    /**
     * Defines whether to supress output from executed commands.
     *
     * @var bool
     */
    #[Option('quiet', 'q', 'Supresses command output.', InputOption::OPTIONAL)]
    public bool $quiet = false;

    /**
     * Defines whether to create TypeScript assets instead of JavaScript files.
     *
     * @var null|bool
     */
    #[Option('typescript', null, 'Create TypeScript assets instead of JavaScript.', InputOption::NEGATABLE)]
    public null|bool $useTypescript = null;

    /**
     * Defines whether to build the assets after installation.
     *
     * @var bool
     */
    #[Option('build', null, 'Build the assets after installation.', InputOption::NEGATABLE)]
    public bool $buildAssets = true;

    /**
     * Defines a list of plugins to include in the build script.
     *
     * @var list<array{import:string,plugin:string}>
     */
    protected array $plugins = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!class_exists('\Brickhouse\Process\Process')) {
            $this->error('Brickhouse\Process could not be found. Have you installed development packages?');
            return 1;
        }

        $assetsDirectory = 'assets';
        if (!@is_dir($assetsDirectory)) {
            @mkdir($assetsDirectory, recursive: true);
        }

        $plugins = multiselect(
            label: 'Would you like any optional plugins?',
            choices: [
                'TailwindCSS',
                'LessCSS',
            ],
        );

        if ($this->useTypescript === null) {
            $this->useTypescript = confirm('Would you like to use TypeScript?');
        }

        // 1. Install esbuild
        if (!$this->installEsbuild()) {
            return 1;
        }

        // 2. Create initial assets
        if (!$this->createInitialAssets()) {
            return 1;
        }

        // 3. Install selected esbuild plugins
        if (!$this->installEsbuildPlugins($plugins)) {
            return 1;
        }

        // 4. Create esbuild configuration
        if (!$this->createEsbuildConfiguration()) {
            return 1;
        }

        // 5. Build the created assets, if required.
        if ($this->buildAssets && $this->call('build') !== 0) {
            return 1;
        }

        return 0;
    }

    /**
     * Installs esbuild in the project using the detected JavaScript package manager.
     *
     * Currently supports NPM, Yarn, Bun and Deno
     *
     * @return bool
     */
    protected function installEsbuild(): bool
    {
        $this->installPackage('esbuild');

        return true;
    }

    /**
     * Detects the projects JavaScript package manager, if any.
     *
     * @param string    $package    Name of the package.
     *
     * @return void
     */
    protected function installPackage(string $package): void
    {
        if (!$this->quiet) {
            $this->info("Installing {$package}...");
        }

        $packageManager = $this->detectPackageManager();
        if ($packageManager === null) {
            $packageManager = 'npm';

            // Copy the package.json stub to the project if none is found.
            file_put_contents(
                'package.json',
                file_get_contents(__DIR__ . '/../../Stubs/package.stub.json')
            );
        }

        $command = match ($packageManager) {
            'npm' => "npm install --save-dev {$package}",
            'yarn' => "yarn add --dev {$package}",
            'bun' => "bun add --dev {$package}",
            'deno' => "deno add --dev npm:{$package}",
        };

        if (!$this->quiet) {
            $this->writeln("> {$command}");
        }

        \Brickhouse\Process\Process::execute($command, callback: function (int $mode, string $text) {
            if ($mode === \Brickhouse\Process\Process::STDERR) {
                $this->error($text);
            } else if (!$this->quiet) {
                $this->writeln($text);
            }
        });
    }

    /**
     * Detects the projects JavaScript package manager, if any.
     *
     * If none is found, returns `null`.
     *
     * @return null|'npm'|'yarn'|'bun'|'deno'
     */
    protected function detectPackageManager(): null|string
    {
        if (file_exists('package-lock.json')) {
            return 'npm';
        }

        if (file_exists('yarn.json')) {
            return 'yarn';
        }

        if (file_exists('bun.lock')) {
            return 'bun';
        }

        if (file_exists('deno.json') || file_exists('deno.lock')) {
            return 'deno';
        }

        return null;
    }

    /**
     * Installs all the esbuild plugins selected in `$plugins`.
     *
     * @param list<string>  $plugins
     *
     * @return bool
     */
    protected function installEsbuildPlugins(array $plugins): bool
    {
        try {
            // TailwindCSS
            if (in_array('TailwindCSS', $plugins)) {
                $this->installTailwind();
            }

            // LessCSS
            if (in_array('LessCSS', $plugins)) {
                $this->installLessCSS();
            }
        } catch (\Throwable $e) {
            $this->error("Failed to install esbuild plugins: {$e->getMessage()}");
            return false;
        }

        return true;
    }

    /**
     * Creates a configuration for esbuild in the project directory.
     *
     * @return bool
     */
    protected function createEsbuildConfiguration(): bool
    {
        // Read the stub from the package root.
        $configurationStub = file_get_contents(__DIR__ . '/../../Stubs/build.stub.mjs');

        // Update the imports
        $configurationStub = str_ireplace(
            '// === ESBUILD_IMPORTS ===',
            join("\n", array_column($this->plugins, 'import')),
            $configurationStub,
        );

        // Update the esbuild plugins
        $configurationStub = str_ireplace(
            '// === ESBUILD_PLUGINS ===',
            join("\n    ", array_column($this->plugins, 'plugin')),
            $configurationStub,
        );

        // Create the build script into the project
        file_put_contents(
            'assets' . DIRECTORY_SEPARATOR . 'build.mjs',
            $configurationStub
        );

        return true;
    }

    /**
     * Copies some initial assets to the project directory.
     *
     * @return boolean
     */
    protected function createInitialAssets(): bool
    {
        // Copy JS/TS stub to project directory
        if ($this->useTypescript === true) {
            file_put_contents(
                'assets' . DIRECTORY_SEPARATOR . 'app.ts',
                file_get_contents(__DIR__ . '/../../Stubs/app.stub.ts')
            );
        } else {
            file_put_contents(
                'assets' . DIRECTORY_SEPARATOR . 'app.js',
                file_get_contents(__DIR__ . '/../../Stubs/app.stub.js')
            );
        }

        return true;
    }

    /**
     * Installs the TailwindCSS plugin for esbuild.
     *
     * @return bool
     */
    protected function installTailwind(): bool
    {
        $this->installPackage('esbuild-plugin-tailwindcss');

        if (file_exists('tailwind.config.js')) {
            $replace = confirm(
                label: '"tailwind.config.js" already exists. Replace it?',
                hint: 'A backup of the file will be made.'
            );

            if (!$replace) {
                $this->warning("Cancelled...");
                return false;
            }

            if (!$this->quiet) {
                $this->info('Creating backup of "tailwind.config.js"...');
            }

            $backupTimestamp = new \DateTime()->format('YmdHis');
            copy('tailwind.config.js', "tailwind.config.js.backup-{$backupTimestamp}");
        }

        // Copy configuration stub to project directory
        file_put_contents(
            'tailwind.config.js',
            file_get_contents(__DIR__ . '/../../Stubs/tailwind/tailwind.config.stub.js')
        );

        // Copy CSS stub to project directory
        file_put_contents(
            'assets' . DIRECTORY_SEPARATOR . 'app.css',
            file_get_contents(__DIR__ . '/../../Stubs/tailwind/app.stub.css')
        );

        // Prepend CSS import in asset script.
        $header = <<<EOF
        import './app.css'

        EOF;

        if ($this->useTypescript === true) {
            $scriptFile = 'assets' . DIRECTORY_SEPARATOR . 'app.ts';
        } else {
            $scriptFile = 'assets' . DIRECTORY_SEPARATOR . 'app.js';
        }

        file_put_contents($scriptFile, $header . "\n" . file_get_contents($scriptFile));

        $this->plugins[] = [
            'import' => 'import tailwindPlugin from "esbuild-plugin-tailwindcss";',
            'plugin' => 'tailwindPlugin(),',
        ];

        return true;
    }

    /**
     * Installs the LessCSS plugin for esbuild.
     *
     * @return void
     */
    protected function installLessCSS(): void
    {
        $this->installPackage('esbuild-plugin-less');

        $this->plugins[] = [
            'import' => 'import { lessLoader } from "esbuild-plugin-less";',
            'plugin' => 'lessLoader(),',
        ];
    }
}
