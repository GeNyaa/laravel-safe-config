<?php

namespace GeNyaa\LaravelSafeConfig\LaravelSafeConfig;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use GeNyaa\LaravelSafeConfig\LaravelSafeConfig\Commands\LaravelSafeConfigCommand;

class LaravelSafeConfigServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-safe-config')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_safe_config_table')
            ->hasCommand(LaravelSafeConfigCommand::class);
    }
}
