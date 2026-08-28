<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * A 404: the file (or folder) is not there. A delete that lands here is
 * safe to treat as already done.
 */
final class NotFound extends ImageKitError
{
    public static function fromResponse(Response $response): self
    {
        return new self($response->status(), self::bodyString($response, 'message'), self::bodyString($response, 'help'));
    }
}
