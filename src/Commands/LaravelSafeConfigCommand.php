<?php

namespace GeNyaa\LaravelSafeConfig\LaravelSafeConfig\Commands;

use Illuminate\Console\Command;

class LaravelSafeConfigCommand extends Command
{
    public $signature = 'laravel-safe-config';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
