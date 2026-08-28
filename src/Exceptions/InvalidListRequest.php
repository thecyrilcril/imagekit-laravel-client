<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

/**
 * A ListRequest that ImageKit would reject: a page size outside 1–1000 or a
 * negative skip. Thrown when the request is built, so a bad paging loop
 * fails before its first request rather than on a 400.
 */
final class InvalidListRequest extends ImageKitClientException
{
    public static function limitOutOfRange(int $limit, int $max): self
    {
        return new self(sprintf('A ListRequest [limit] must be between 1 and %d; %d given.', $max, $limit));
    }

    public static function negativeSkip(int $skip): self
    {
        return new self(sprintf('A ListRequest [skip] cannot be negative; %d given.', $skip));
    }
}
