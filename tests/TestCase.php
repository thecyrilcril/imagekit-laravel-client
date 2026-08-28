<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Override;
use Thecyrilcril\ImageKitClient\Http\Sleeper;
use Thecyrilcril\ImageKitClient\ImageKitClientServiceProvider;
use Thecyrilcril\ImageKitClient\Tests\Support\FakeSleeper;

abstract class TestCase extends Orchestra
{
    protected FakeSleeper $sleeper;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // No test may wait: every sleep the Connection asks for is recorded
        // here instead. SystemSleeperTest forgets this instance to prove the
        // real binding.
        $this->sleeper = new FakeSleeper;
        $this->app->instance(Sleeper::class, $this->sleeper);
    }

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
