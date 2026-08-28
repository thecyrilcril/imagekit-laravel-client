<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * A 429 the Client will not retry again: either http.retries is 0, or the
 * one retry after the X-RateLimit-Reset wait was throttled too.
 * retryAfterMilliseconds is that header from the last response, so a caller
 * that wants to wait itself knows how long.
 */
final class RateLimited extends ImageKitError
{
    public function __construct(
        int $status,
        ?string $imageKitMessage,
        ?string $help,
        public readonly int $retryAfterMilliseconds,
    ) {
        parent::__construct($status, $imageKitMessage, $help);
    }

    public static function fromResponse(Response $response, int $retryAfterMilliseconds): self
    {
        return new self(
            $response->status(),
            self::bodyString($response, 'message'),
            self::bodyString($response, 'help'),
            $retryAfterMilliseconds,
        );
    }
}
