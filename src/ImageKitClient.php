<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient;

use Thecyrilcril\ImageKitClient\Contracts\Client;
use Thecyrilcril\ImageKitClient\Contracts\Files;
use Thecyrilcril\ImageKitClient\Contracts\Urls;

final class ImageKitClient implements Client
{
    public function __construct(
        private readonly Files $files,
        private readonly Urls $urls,
    ) {}

    public function files(): Files
    {
        return $this->files;
    }

    public function urls(): Urls
    {
        return $this->urls;
    }
}
