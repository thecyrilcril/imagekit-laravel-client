<?php

declare(strict_types=1);

it('merges the package configuration defaults', function (): void {
    expect(config('imagekit-client.transformation_position'))->toBe('path')
        ->and(config('imagekit-client.http.timeout'))->toBe(30)
        ->and(config('imagekit-client.http.retries'))->toBe(0);
});

it('reads every configuration key from the environment', function (): void {
    $environment = [
        'IMAGEKIT_PUBLIC_KEY' => 'public_from_env',
        'IMAGEKIT_PRIVATE_KEY' => 'private_from_env',
        'IMAGEKIT_URL_ENDPOINT' => 'https://ik.imagekit.io/from-env',
        'IMAGEKIT_TRANSFORMATION_POSITION' => 'query',
        'IMAGEKIT_HTTP_TIMEOUT' => '45',
        'IMAGEKIT_HTTP_RETRIES' => '2',
    ];

    foreach ($environment as $name => $value) {
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        putenv(sprintf('%s=%s', $name, $value));
    }

    try {
        $config = require __DIR__.'/../config/imagekit-client.php';
    } finally {
        foreach (array_keys($environment) as $name) {
            unset($_ENV[$name], $_SERVER[$name]);
            putenv($name);
        }
    }

    expect($config)->toBe([
        'public_key' => 'public_from_env',
        'private_key' => 'private_from_env',
        'url_endpoint' => 'https://ik.imagekit.io/from-env',
        'transformation_position' => 'query',
        'http' => [
            'timeout' => '45',
            'retries' => '2',
        ],
    ]);
});

it('publishes the configuration file under its own tag', function (): void {
    $target = config_path('imagekit-client.php');

    if (file_exists($target)) {
        unlink($target);
    }

    try {
        $this->artisan('vendor:publish', ['--tag' => 'imagekit-client-config'])->assertSuccessful();

        expect(file_exists($target))->toBeTrue()
            ->and(file_get_contents($target))->toBe(file_get_contents(__DIR__.'/../config/imagekit-client.php'));
    } finally {
        if (file_exists($target)) {
            unlink($target);
        }
    }
});
