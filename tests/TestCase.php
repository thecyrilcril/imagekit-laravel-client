<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Tests;

use DateTimeImmutable;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;
use Thecyrilcril\ImageKitClient\Http\Sleeper;
use Thecyrilcril\ImageKitClient\ImageKitClientServiceProvider;
use Thecyrilcril\ImageKitClient\Tests\Support\FakeClock;
use Thecyrilcril\ImageKitClient\Tests\Support\FakeSleeper;
use Thecyrilcril\ImageKitClient\Time\Clock;

abstract class TestCase extends Orchestra
{
    /**
     * Unix time of the instant every test's Clock reports.
     */
    public const int NOW = 1_700_000_000;

    protected FakeSleeper $sleeper;

    protected FakeClock $clock;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // No test may wait: every sleep the Connection asks for is recorded
        // here instead. SystemSleeperTest forgets this instance to prove the
        // real binding.
        $this->sleeper = new FakeSleeper;
        $this->app->instance(Sleeper::class, $this->sleeper);

        // Nor may a test depend on the wall clock: an expiring signature is
        // made against this fixed instant. SystemClockTest forgets it.
        $this->clock = new FakeClock(new DateTimeImmutable('@'.self::NOW));
        $this->app->instance(Clock::class, $this->clock);
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
