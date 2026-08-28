<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient;

use Override;
use Thecyrilcril\ImageKitClient\Contracts\Client;
use Thecyrilcril\ImageKitClient\Contracts\Files;
use Thecyrilcril\ImageKitClient\Contracts\Urls;

/**
 * The concrete Client. Named apart from the facade on purpose, so that
 * importing both never needs an alias.
 */
final readonly class ClientManager implements Client
{
    public function __construct(
        private Files $files,
        private Urls $urls,
    ) {}

    #[Override]
    public function files(): Files
    {
        return $this->files;
    }

    #[Override]
    public function urls(): Urls
    {
        return $this->urls;
    }
}
