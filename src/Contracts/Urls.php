<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Contracts;

use Thecyrilcril\ImageKitClient\Exceptions\InvalidTransformation;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidUrlRequest;
use Thecyrilcril\ImageKitClient\Urls\UrlRequest;

/**
 * The URL area: turning a path or absolute source plus a Transformation into
 * a delivery URL, signed when asked. Pure string building, no HTTP.
 */
interface Urls
{
    /**
     * @throws InvalidTransformation when a Transformation key is unknown
     * @throws InvalidUrlRequest when the request cannot describe a URL
     */
    public function build(UrlRequest $request): string;
}
