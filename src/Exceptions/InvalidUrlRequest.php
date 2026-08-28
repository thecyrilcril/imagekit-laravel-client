<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

/**
 * Thrown when a UrlRequest cannot describe a URL: no source, or two.
 */
final class InvalidUrlRequest extends ImageKitClientException
{
    public static function missingSource(): self
    {
        return new self('A UrlRequest needs a [path] or a [src]; neither was given.');
    }

    public static function ambiguousSource(): self
    {
        return new self('A UrlRequest takes a [path] or a [src], not both.');
    }
}
