<?php

namespace GeNyaa\LaravelSafeConfig\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallSafeConfigCommand extends Command
{
    protected $signature = 'safe-config:install {--force : Overwrite an existing safe-config.php file}';

    protected $description = 'Create the safe-config.php file in the application base directory';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $safeConfigPath = $this->laravel->basePath('safe-config.php');

        if ($this->files->exists($safeConfigPath) && ! $this->option('force')) {
            $this->components->warn('The safe-config.php file already exists. Use --force to overwrite it.');

            return self::FAILURE;
        }

        $this->files->put($safeConfigPath, $this->stubContents());

        $this->components->info('The safe-config.php file was created successfully.');

        return self::SUCCESS;
    }

    protected function stubContents(): string
    {
        return <<<'PHP'
<?php

return [
    // 'APP_KEY',
    // 'DB_PASSWORD',
];
PHP;
    }
}
