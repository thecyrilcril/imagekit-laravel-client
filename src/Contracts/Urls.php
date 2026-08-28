<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Contracts;

use Thecyrilcril\ImageKitClient\Exceptions\InvalidTransformation;
use Thecyrilcril\ImageKitClient\Urls\UrlRequest;

/**
 * The URL area: turning a path or absolute source plus a Transformation into
 * a delivery URL, signed when asked. Pure string building, no HTTP.
 */
interface Urls
{
    /**
     * @throws InvalidTransformation when a Transformation key or value cannot be rendered
     */
    public function build(UrlRequest $request): string;
}
