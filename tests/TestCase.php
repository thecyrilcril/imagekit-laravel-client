<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Thecyrilcril\ImageKitClient\ImageKitClientServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ImageKitClientServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        $config->set('imagekit-client.public_key', 'public_test');
        $config->set('imagekit-client.private_key', 'private_test');
        $config->set('imagekit-client.url_endpoint', 'https://ik.imagekit.io/test');
    }
}
