<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

/**
 * Thrown when a UrlRequest cannot describe a URL: no source, or two, or a
 * signing option that does not fit the source.
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

    public static function cannotSignSrc(): self
    {
        return new self('A UrlRequest with a [src] cannot be signed: signing strips the configured endpoint, and a [src] may live on any endpoint. Give a [path] instead.');
    }

    public static function expiryWithoutSigning(): self
    {
        return new self('A UrlRequest with [expiresIn] must also be [signed]; ik-t only means something next to ik-s.');
    }

    public static function expiryNotPositive(int $expiresIn): self
    {
        return new self(sprintf('A UrlRequest [expiresIn] must be a positive number of seconds; %d given.', $expiresIn));
    }
}
