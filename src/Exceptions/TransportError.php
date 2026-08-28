<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

use Illuminate\Http\Client\ConnectionException;

/**
 * ImageKit never answered: DNS failure, refused connection, or timeout.
 * Distinct from RequestFailed so "ImageKit was unreachable" can be told
 * from "ImageKit said no". The connection exception is the previous.
 */
final class TransportError extends ImageKitClientException
{
    public static function wrap(ConnectionException $exception): self
    {
        return new self($exception->getMessage(), previous: $exception);
    }
}
