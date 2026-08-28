<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Facades;

use Illuminate\Support\Facades\Facade;
use Override;
use Thecyrilcril\ImageKitClient\Contracts\Client;
use Thecyrilcril\ImageKitClient\Contracts\Files;
use Thecyrilcril\ImageKitClient\Contracts\Urls;
use Thecyrilcril\ImageKitClient\Testing\ClientFake;

/**
 * @method static \Thecyrilcril\ImageKitClient\Contracts\Files files()
 * @method static \Thecyrilcril\ImageKitClient\Contracts\Urls urls()
 *
 * @see Client
 */
final class ImageKitClient extends Facade
{
    /**
     * Replace the Client with a fake that records uploads, deletions and
     * listings and never sends a request. The facade, and anything that
     * injects the Client or the Files contract, get the fake from here on.
     */
    public static function fake(): ClientFake
    {
        $app = self::getFacadeApplication();

        $fake = new ClientFake($app->make(Urls::class));

        self::swap($fake);
        $app->instance(Files::class, $fake);

        return $fake;
    }

    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
