<?php

namespace GeNyaa\LaravelSafeConfig\Tests;

use GeNyaa\LaravelSafeConfig\LaravelSafeConfigServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelSafeConfigServiceProvider::class,
        ];
    }
}
