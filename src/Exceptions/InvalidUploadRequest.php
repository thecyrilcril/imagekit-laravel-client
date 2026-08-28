<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

/**
 * Thrown when an UploadRequest could never succeed: empty content, a source
 * in the wrong form, or no file name. Caught before any request leaves.
 */
final class InvalidUploadRequest extends ImageKitClientException
{
    public static function emptyBytes(): self
    {
        return new self('An UploadSource made from bytes has no bytes; ImageKit stores nothing for an empty file.');
    }

    public static function notADataUri(): self
    {
        return new self('An UploadSource made from a data URI must start with "data:"; give the full URI, not the bare base64.');
    }

    public static function notAUrl(string $url): self
    {
        return new self(sprintf('An UploadSource made from a URL must be an http:// or https:// URL that ImageKit can fetch; "%s" given.', $url));
    }

    public static function emptyFileName(): self
    {
        return new self('An UploadRequest needs a [fileName]; ImageKit stores every file under a name.');
    }
}
