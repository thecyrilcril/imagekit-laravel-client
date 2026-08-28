<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * Any 4xx or 5xx that is not a 404 or a 429, after retries.
 */
final class RequestFailed extends ImageKitError
{
    public static function fromResponse(Response $response): self
    {
        return new self($response->status(), self::bodyString($response, 'message'), self::bodyString($response, 'help'));
    }
}
