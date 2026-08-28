<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Facades;

use Illuminate\Support\Facades\Facade;
use Override;
use Thecyrilcril\ImageKitClient\Contracts\Client;

/**
 * @method static \Thecyrilcril\ImageKitClient\Contracts\Files files()
 * @method static \Thecyrilcril\ImageKitClient\Contracts\Urls urls()
 *
 * @see Client
 */
final class ImageKitClient extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
