<?php

namespace GeNyaa\LaravelSafeConfig\LaravelSafeConfig\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \GeNyaa\LaravelSafeConfig\LaravelSafeConfig\LaravelSafeConfig
 */
class LaravelSafeConfig extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \GeNyaa\LaravelSafeConfig\LaravelSafeConfig\LaravelSafeConfig::class;
    }
}
