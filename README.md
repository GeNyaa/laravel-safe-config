# Laravel Safe Config

[![Latest Version on Packagist](https://img.shields.io/packagist/v/genyaa/laravel-safe-config.svg?style=flat-square)](https://packagist.org/packages/genyaa/laravel-safe-config)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/genyaa/laravel-safe-config/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/genyaa/laravel-safe-config/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/genyaa/laravel-safe-config/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/genyaa/laravel-safe-config/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/genyaa/laravel-safe-config.svg?style=flat-square)](https://packagist.org/packages/genyaa/laravel-safe-config)

`genyaa/laravel-safe-config` overrides Laravel's default `config:cache` command so selected environment variables are not being written as to file values into the cached config file.

## Installation

You can install the package via composer:

```bash
composer require genyaa/laravel-safe-config
```

Create a `safe-config.php` file in the root of your Laravel project:

```bash
php artisan safe-config:install
```

If the file already exists and you want to replace it, use:

```bash
php artisan safe-config:install --force
```

This file should return a list of environment variable names that must remain secret after config caching:

```php
return [
	'APP_KEY',
	'DB_PASSWORD',
	// ...
];
```


## Usage

Once the package service provider is loaded, Laravel resolves `config:cache` through this package.

```bash
php artisan config:cache
```

Any environment variable listed in your root `safe-config.php` file will be emitted into the cached config file as a runtime `getenv()` lookup.

For example:

```php
return [
	'APP_KEY',
	'DB_PASSWORD',
	// ...
];
```

During `config:cache`, the package automatically discovers which config values depend on each listed environment variable by probing fresh configuration snapshots. Those cached config values are then not permanently frozen at cache-build time. When the cache file is loaded, Laravel will read them through `getenv()`, and if `getenv()` returns `false`, the cache falls back to the value that existed when `config:cache` was run.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

<a href="https://github.com/GeNyaa/laravel-safe-config/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=GeNyaa/laravel-safe-config" alt="Contributors" />
</a>

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
