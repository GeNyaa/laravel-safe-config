<?php

afterEach(function () {
    $safeConfigPath = app()->basePath('safe-config.php');

    if (file_exists($safeConfigPath)) {
        unlink($safeConfigPath);
    }
});

it('creates the safe-config file in the application base directory', function () {
    $safeConfigPath = app()->basePath('safe-config.php');

    $this->artisan('safe-config:install')
        ->expectsOutputToContain('created successfully')
        ->assertSuccessful();

    expect($safeConfigPath)
        ->toBeFile()
        ->and(file_get_contents($safeConfigPath))
        ->toContain("// 'APP_KEY',");
});

it('does not overwrite an existing safe-config file without force', function () {
    $safeConfigPath = app()->basePath('safe-config.php');

    file_put_contents($safeConfigPath, "<?php\n\nreturn ['APP_URL'];\n");

    $this->artisan('safe-config:install')
        ->expectsOutputToContain('already exists')
        ->assertFailed();

    expect(file_get_contents($safeConfigPath))
        ->toBe("<?php\n\nreturn ['APP_URL'];\n");
});

it('overwrites an existing safe-config file when forced', function () {
    $safeConfigPath = app()->basePath('safe-config.php');

    file_put_contents($safeConfigPath, "<?php\n\nreturn ['APP_URL'];\n");

    $this->artisan('safe-config:install --force')
        ->expectsOutputToContain('created successfully')
        ->assertSuccessful();

    expect(file_get_contents($safeConfigPath))
        ->toContain("// 'APP_KEY',")
        ->not->toContain("'APP_URL'");
});
