<?php

namespace GeNyaa\LaravelSafeConfig\Commands;

use Illuminate\Foundation\Console\ConfigCacheCommand as LaravelConfigCacheCommand;
use Illuminate\Support\Arr;
use LogicException;
use Throwable;

class SafeConfigCacheCommand extends LaravelConfigCacheCommand
{
    protected $description = 'Create a cache file for faster and safer configuration loading';

    public function handle(): int
    {
        $this->callSilent('config:clear');

        $config = $this->getFreshConfiguration();
        $configPath = $this->laravel->getCachedConfigPath();

        $this->files->put($configPath, $this->buildCacheFileContents($config));

        try {
            require $configPath;
        } catch (Throwable $e) {
            $this->files->delete($configPath);

            foreach (Arr::dot($config) as $key => $value) {
                try {
                    eval(var_export($value, true).';');
                } catch (Throwable $e) {
                    throw new LogicException("Your configuration files could not be serialized because the value at \"{$key}\" is non-serializable.", 0, $e);
                }
            }

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }

        $this->components->info('Configuration cached successfully.');

        return self::SUCCESS;
    }

    protected function buildCacheFileContents(array $config): string
    {
        [$config, $replacements] = $this->markDynamicEnvironmentValues($config);

        $export = var_export($config, true);

        foreach ($replacements as $replacement) {
            $export = str_replace(
                var_export($replacement['placeholder'], true),
                $replacement['expression'],
                $export,
            );
        }

        return '<?php return '.$export.';'.PHP_EOL;
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array{placeholder: string, expression: string}>}
     */
    protected function markDynamicEnvironmentValues(array $config): array
    {
        $replacements = [];

        foreach ($this->alwaysGetenvConfiguration($config) as $environmentVariable => $paths) {
            foreach (array_values(array_unique($paths)) as $index => $path) {
                if (! Arr::has($config, $path)) {
                    continue;
                }

                $placeholder = "__laravel_safe_config_getenv__{$environmentVariable}__{$index}__";

                $replacements[] = [
                    'placeholder' => $placeholder,
                    'expression' => $this->compileGetenvExpression($environmentVariable, Arr::get($config, $path)),
                ];

                Arr::set($config, $path, $placeholder);
            }
        }

        return [$config, $replacements];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function alwaysGetenvConfiguration(array $config): array
    {
        $alwaysGetenv = $this->readSafeConfigFile();

        if ($alwaysGetenv === []) {
            return [];
        }

        if (! array_is_list($alwaysGetenv)) {
            throw new LogicException('The safe-config.php file must return a list of environment variable names.');
        }

        $normalized = [];

        foreach ($alwaysGetenv as $environmentVariable) {
            if (! is_string($environmentVariable) || $environmentVariable === '') {
                throw new LogicException('Each entry in safe-config.php must be a non-empty environment variable name.');
            }

            $normalizedPaths = $this->discoverConfigPathsForEnvironmentVariable($environmentVariable, $config);

            if ($normalizedPaths !== []) {
                $normalized[$environmentVariable] = $normalizedPaths;
            }
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    protected function readSafeConfigFile(): array
    {
        $safeConfigPath = $this->safeConfigPath();

        if (! is_file($safeConfigPath)) {
            return [];
        }

        $alwaysGetenv = require $safeConfigPath;

        if (! is_array($alwaysGetenv)) {
            throw new LogicException('The safe-config.php file must return an array of environment variable names.');
        }

        return $alwaysGetenv;
    }

    protected function safeConfigPath(): string
    {
        return $this->laravel->basePath('safe-config.php');
    }

    /**
     * @return list<string>
     */
    protected function discoverConfigPathsForEnvironmentVariable(string $environmentVariable, array $config): array
    {
        $probeValues = [
            "__laravel_safe_config_probe__{$environmentVariable}__one__",
            "__laravel_safe_config_probe__{$environmentVariable}__two__",
        ];

        $firstProbeConfig = $this->getFreshConfigurationWithEnvironmentVariable($environmentVariable, $probeValues[0]);
        $secondProbeConfig = $this->getFreshConfigurationWithEnvironmentVariable($environmentVariable, $probeValues[1]);

        return array_values(array_unique(array_filter(
            $this->findChangedConfigPaths($firstProbeConfig, $secondProbeConfig),
            fn (string $path) => Arr::has($config, $path),
        )));
    }

    protected function getFreshConfigurationWithEnvironmentVariable(string $environmentVariable, string $value): array
    {
        return $this->withEnvironmentVariable($environmentVariable, $value, function (): array {
            return $this->getFreshConfiguration();
        });
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    protected function withEnvironmentVariable(string $environmentVariable, string $value, callable $callback): mixed
    {
        $initialGetenvValue = getenv($environmentVariable);
        $initialEnvValueWasDefined = array_key_exists($environmentVariable, $_ENV);
        $initialEnvValue = $_ENV[$environmentVariable] ?? null;
        $initialServerValueWasDefined = array_key_exists($environmentVariable, $_SERVER);
        $initialServerValue = $_SERVER[$environmentVariable] ?? null;

        putenv("{$environmentVariable}={$value}");
        $_ENV[$environmentVariable] = $value;
        $_SERVER[$environmentVariable] = $value;

        try {
            return $callback();
        } finally {
            if ($initialGetenvValue === false) {
                putenv($environmentVariable);
            } else {
                putenv("{$environmentVariable}={$initialGetenvValue}");
            }

            if ($initialEnvValueWasDefined) {
                $_ENV[$environmentVariable] = $initialEnvValue;
            } else {
                unset($_ENV[$environmentVariable]);
            }

            if ($initialServerValueWasDefined) {
                $_SERVER[$environmentVariable] = $initialServerValue;
            } else {
                unset($_SERVER[$environmentVariable]);
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function findChangedConfigPaths(mixed $left, mixed $right, string $path = ''): array
    {
        if (is_array($left) && is_array($right)) {
            $changedPaths = [];

            foreach (array_unique(array_merge(array_keys($left), array_keys($right))) as $key) {
                $childPath = $path === '' ? (string) $key : "{$path}.{$key}";
                $childLeft = $left[$key] ?? null;
                $childRight = $right[$key] ?? null;

                if (! array_key_exists($key, $left) || ! array_key_exists($key, $right)) {
                    $changedPaths[] = $childPath;

                    continue;
                }

                array_push($changedPaths, ...$this->findChangedConfigPaths($childLeft, $childRight, $childPath));
            }

            return $changedPaths;
        }

        if ($left !== $right && $path !== '') {
            return [$path];
        }

        return [];
    }

    protected function compileGetenvExpression(string $environmentVariable, mixed $fallback): string
    {
        return sprintf(
            '(static function (): mixed { $value = getenv(%s); return $value !== false ? $value : %s; })()',
            var_export($environmentVariable, true),
            var_export($fallback, true),
        );
    }
}

