<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * ImageKit answered, and said no. Catch this for any error status; catch
 * NotFound, RateLimited or RequestFailed to tell them apart.
 *
 * ImageKit error bodies are `{"message": "...", "help": "..."}`. Both are
 * kept raw when present; getMessage() folds the status and the message into
 * one log-ready line.
 */
abstract class ImageKitError extends ImageKitClientException
{
    public function __construct(
        public readonly int $status,
        public readonly ?string $imageKitMessage,
        public readonly ?string $help,
    ) {
        parent::__construct($imageKitMessage === null
            ? sprintf('ImageKit responded with HTTP %d.', $status)
            : sprintf('ImageKit responded with HTTP %d: %s', $status, $imageKitMessage));
    }

    /**
     * A body field that is absent, empty, or not a string (an HTML error
     * page, say) reads as null, so getMessage() falls back to the status.
     */
    protected static function bodyString(Response $response, string $key): ?string
    {
        $value = $response->json($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
