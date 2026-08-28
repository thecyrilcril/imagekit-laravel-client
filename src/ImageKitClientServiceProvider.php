<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;
use Thecyrilcril\ImageKitClient\Contracts\Client;
use Thecyrilcril\ImageKitClient\Contracts\Files;
use Thecyrilcril\ImageKitClient\Contracts\Urls;
use Thecyrilcril\ImageKitClient\Files\FilesApi;
use Thecyrilcril\ImageKitClient\Http\Sleeper;
use Thecyrilcril\ImageKitClient\Http\SystemSleeper;
use Thecyrilcril\ImageKitClient\Time\Clock;
use Thecyrilcril\ImageKitClient\Time\SystemClock;
use Thecyrilcril\ImageKitClient\Urls\UrlBuilder;

final class ImageKitClientServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/imagekit-client.php', 'imagekit-client');

        $this->app->singleton(Configuration::class, function (): Configuration {
            /** @var array<string, mixed> $config */
            $config = config('imagekit-client', []);

            return Configuration::fromArray($config);
        });

        $this->app->singleton(Sleeper::class, SystemSleeper::class);
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(Files::class, FilesApi::class);
        $this->app->singleton(Urls::class, UrlBuilder::class);

        // Both areas take Configuration, so resolving the Client builds it: a
        // missing credential fails here, before any request is sent.
        $this->app->singleton(Client::class, fn (Application $app): Client => new ClientManager(
            $app->make(Files::class),
            $app->make(Urls::class),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/imagekit-client.php' => config_path('imagekit-client.php'),
            ], 'imagekit-client-config');
        }
    }
}
