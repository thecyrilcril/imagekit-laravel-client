<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Http;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Thecyrilcril\ImageKitClient\Configuration;
use Thecyrilcril\ImageKitClient\Exceptions\NotFound;
use Thecyrilcril\ImageKitClient\Exceptions\RateLimited;
use Thecyrilcril\ImageKitClient\Exceptions\RequestFailed;
use Thecyrilcril\ImageKitClient\Exceptions\TransportError;

/**
 * Every request to ImageKit leaves through here. One place builds the
 * pending request (base URL, Basic auth, timeout, JSON) and one place applies
 * the retry policy and turns a bad outcome into a typed exception.
 */
final readonly class Connection
{
    private const string API_BASE_URL = 'https://api.imagekit.io/v1';

    private const string UPLOAD_BASE_URL = 'https://upload.imagekit.io/api/v1';

    /**
     * Milliseconds until the current rate-limit window resets.
     */
    private const string RATE_LIMIT_RESET_HEADER = 'X-RateLimit-Reset';

    public function __construct(
        private Factory $http,
        private Configuration $configuration,
        private Sleeper $sleeper,
    ) {}

    /**
     * Send a request to the management API: everything except upload.
     *
     * @param  Closure(PendingRequest): Response  $send  Builds and sends the request on a fresh, configured pending request
     */
    public function api(Closure $send): Response
    {
        return $this->attempt(self::API_BASE_URL, $send);
    }

    /**
     * Send a request to the upload API.
     *
     * @param  Closure(PendingRequest): Response  $send  Builds and sends the request on a fresh, configured pending request
     */
    public function upload(Closure $send): Response
    {
        return $this->attempt(self::UPLOAD_BASE_URL, $send);
    }

    /**
     * The retry policy. A transport error or a 5xx is retried up to
     * http.retries more times, straight away. A 429 is retried once, after
     * waiting out X-RateLimit-Reset, and only when http.retries is above 0.
     * Every other outcome is final on the first response.
     *
     * @param  Closure(PendingRequest): Response  $send
     */
    private function attempt(string $baseUrl, Closure $send): Response
    {
        $retriesLeft = $this->configuration->retries;
        $rateLimitRetryLeft = $this->configuration->retries > 0;

        while (true) {
            try {
                $response = $send($this->pendingRequest($baseUrl));
            } catch (ConnectionException $exception) {
                if ($retriesLeft-- > 0) {
                    continue;
                }

                throw TransportError::wrap($exception);
            }

            if ($response->successful()) {
                return $response;
            }

            if ($response->status() === 429) {
                $resetMilliseconds = max(0, (int) $response->header(self::RATE_LIMIT_RESET_HEADER));

                if ($rateLimitRetryLeft) {
                    $rateLimitRetryLeft = false;
                    $this->sleeper->sleep($resetMilliseconds);

                    continue;
                }

                throw RateLimited::fromResponse($response, $resetMilliseconds);
            }

            if ($response->serverError() && $retriesLeft-- > 0) {
                continue;
            }

            throw $response->status() === 404
                ? NotFound::fromResponse($response)
                : RequestFailed::fromResponse($response);
        }
    }

    private function pendingRequest(string $baseUrl): PendingRequest
    {
        return $this->http
            ->baseUrl($baseUrl)
            ->withBasicAuth($this->configuration->privateKey, '')
            ->timeout($this->configuration->timeout)
            ->acceptJson();
    }
}
