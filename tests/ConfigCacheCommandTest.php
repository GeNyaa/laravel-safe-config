<?php

use GeNyaa\LaravelSafeConfig\Commands\SafeConfigCacheCommand;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\ConfigCacheCommand as LaravelConfigCacheCommand;

function safeConfigPath(): string
{
    return app()->basePath('safe-config.php');
}

function writeSafeConfigFile(array $environmentVariables): void
{
    file_put_contents(
        safeConfigPath(),
        "<?php\n\nreturn ".var_export($environmentVariables, true).";\n",
    );
}

afterEach(function () {
    putenv('APP_NAME');

    if (file_exists($this->app->getCachedConfigPath())) {
        unlink($this->app->getCachedConfigPath());
    }

    if (file_exists(safeConfigPath())) {
        unlink(safeConfigPath());
    }
});

it('replaces the framework config cache binding', function () {
    expect($this->app->make(LaravelConfigCacheCommand::class))
        ->toBeInstanceOf(SafeConfigCacheCommand::class);
});

it('registers the replacement as artisan config:cache command', function () {
    $command = $this->app->make(Kernel::class)->all()['config:cache'];

    expect($command)->toBeInstanceOf(SafeConfigCacheCommand::class);
});

it('still caches configuration when safe-config.php does not exist', function () {
    expect(safeConfigPath())->not->toBeFile();

    $this->artisan('config:cache')->assertSuccessful();

    $cachedConfigPath = $this->app->getCachedConfigPath();
    $cachedConfigContents = file_get_contents($cachedConfigPath);
    $cachedConfig = require $cachedConfigPath;

    expect($cachedConfigPath)
        ->toBeFile()
        ->and($cachedConfigContents)
        ->not->toContain("getenv('APP_NAME')")
        ->and($cachedConfig['app']['name'])
        ->toBe(config('app.name'));
});

it('keeps configured environment variables dynamic in the cached config file', function () {
    writeSafeConfigFile(['APP_NAME']);

    putenv('APP_NAME=Build time application name');

    $this->artisan('config:cache')->assertSuccessful();

    $cachedConfigPath = $this->app->getCachedConfigPath();
    $cachedConfigContents = file_get_contents($cachedConfigPath);
    $buildTimeConfig = require $cachedConfigPath;

    expect($cachedConfigContents)
        ->toContain("getenv('APP_NAME')");

    expect($buildTimeConfig['app']['name'])
        ->toBe('Build time application name');

    putenv('APP_NAME=Runtime application name');

    $runtimeConfig = require $cachedConfigPath;

    expect($runtimeConfig['app']['name'])
        ->toBe('Runtime application name');
});

it('falls back to the cached value when getenv returns false', function () {
    writeSafeConfigFile(['APP_NAME']);

    $expectedFallback = config('app.name');

    putenv('APP_NAME=Build time application name');

    $this->artisan('config:cache')->assertSuccessful();

    $cachedConfigPath = $this->app->getCachedConfigPath();

    putenv('APP_NAME');

    $runtimeConfig = require $cachedConfigPath;

    expect($runtimeConfig['app']['name'])
        ->toBe($expectedFallback);
});

it('discovers config locations for listed env variables even when they are unset during caching', function () {
    writeSafeConfigFile(['APP_NAME']);

    putenv('APP_NAME');

    $expectedFallback = config('app.name');

    $this->artisan('config:cache')->assertSuccessful();

    $cachedConfigPath = $this->app->getCachedConfigPath();
    $cachedConfigContents = file_get_contents($cachedConfigPath);
    $fallbackConfig = require $cachedConfigPath;

    expect($cachedConfigContents)
        ->toContain("getenv('APP_NAME')");

    expect($fallbackConfig['app']['name'])
        ->toBe($expectedFallback);

    putenv('APP_NAME=Runtime application name');

    $runtimeConfig = require $cachedConfigPath;

    expect($runtimeConfig['app']['name'])
        ->toBe('Runtime application name');
});
