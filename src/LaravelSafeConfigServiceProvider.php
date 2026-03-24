<?php

namespace GeNyaa\LaravelSafeConfig;


use GeNyaa\LaravelSafeConfig\Commands\InstallSafeConfigCommand;
use GeNyaa\LaravelSafeConfig\Commands\SafeConfigCacheCommand;
use Illuminate\Foundation\Console\ConfigCacheCommand as LaravelConfigCacheCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelSafeConfigServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-safe-config')
            ->hasCommand(InstallSafeConfigCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->extend(LaravelConfigCacheCommand::class, function ($command, $app) {
            return new SafeConfigCacheCommand($app['files']);
        });
    }
}
