<?php

declare(strict_types=1);

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Thecyrilcril\ImageKitClient\Exceptions\RateLimited;
use Thecyrilcril\ImageKitClient\Exceptions\RequestFailed;
use Thecyrilcril\ImageKitClient\Exceptions\TransportError;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;

function rateLimited(int $resetMilliseconds): PromiseInterface
{
    return Http::response(['message' => 'Rate limit exceeded.'], 429, ['X-RateLimit-Reset' => (string) $resetMilliseconds]);
}

it('sends the request once and throws transport when retries are off', function (): void {
    config()->set('imagekit-client.http.retries', 0);
    Http::fake(['api.imagekit.io/*' => Http::failedConnection()]);

    expect(fn () => ImageKitClient::files()->delete('file_123'))->toThrow(TransportError::class)
        ->and($this->sleeper->slept)->toBe([]);
    Http::assertSentCount(1);
});

it('retries a transport error http.retries times before throwing transport', function (): void {
    config()->set('imagekit-client.http.retries', 2);
    Http::fake(['api.imagekit.io/*' => Http::failedConnection()]);

    expect(fn () => ImageKitClient::files()->delete('file_123'))->toThrow(TransportError::class);
    Http::assertSentCount(3);
});

it('recovers when a retry after a transport error succeeds', function (): void {
    config()->set('imagekit-client.http.retries', 1);
    Http::fake(['api.imagekit.io/*' => Http::sequence()
        ->pushResponse(Http::failedConnection())
        ->push(null, 204)]);

    ImageKitClient::files()->delete('file_123');

    Http::assertSentCount(2);
});

it('retries a 5xx http.retries times before throwing request-failed', function (): void {
    config()->set('imagekit-client.http.retries', 1);
    Http::fake(['api.imagekit.io/*' => Http::sequence()->pushStatus(503)->pushStatus(503)]);

    expect(fn () => ImageKitClient::files()->delete('file_123'))
        ->toThrow(fn (RequestFailed $exception) => expect($exception->status)->toBe(503));
    Http::assertSentCount(2);
});

it('recovers when a retry after a 5xx succeeds', function (): void {
    config()->set('imagekit-client.http.retries', 1);
    Http::fake(['api.imagekit.io/*' => Http::sequence()->pushStatus(500)->push(null, 204)]);

    ImageKitClient::files()->delete('file_123');

    Http::assertSentCount(2);
});

it('does not retry a 4xx', function (): void {
    config()->set('imagekit-client.http.retries', 3);
    Http::fake(['api.imagekit.io/*' => Http::response(['message' => 'Bad request.'], 400)]);

    expect(fn () => ImageKitClient::files()->delete('file_123'))->toThrow(RequestFailed::class);
    Http::assertSentCount(1);
});

it('sleeps for X-RateLimit-Reset and retries a 429 once when retries are on', function (): void {
    config()->set('imagekit-client.http.retries', 1);
    Http::fake(['api.imagekit.io/*' => Http::sequence()
        ->pushResponse(rateLimited(1500))
        ->push(null, 204)]);

    ImageKitClient::files()->delete('file_123');

    Http::assertSentCount(2);
    expect($this->sleeper->slept)->toBe([1500]);
});

it('throws rate-limited without sleeping when retries are off', function (): void {
    config()->set('imagekit-client.http.retries', 0);
    Http::fake(['api.imagekit.io/*' => rateLimited(1500)]);

    expect(fn () => ImageKitClient::files()->delete('file_123'))
        ->toThrow(function (RateLimited $exception): void {
            expect($exception->status)->toBe(429)
                ->and($exception->imageKitMessage)->toBe('Rate limit exceeded.')
                ->and($exception->getMessage())->toBe('ImageKit responded with HTTP 429: Rate limit exceeded.')
                ->and($exception->retryAfterMilliseconds)->toBe(1500)
                ->and($this->sleeper->slept)->toBe([]);
        });
    Http::assertSentCount(1);
});

it('throws rate-limited when the one retry is also a 429', function (): void {
    config()->set('imagekit-client.http.retries', 3);
    Http::fake(['api.imagekit.io/*' => Http::sequence()
        ->pushResponse(rateLimited(1500))
        ->pushResponse(rateLimited(800))]);

    expect(fn () => ImageKitClient::files()->delete('file_123'))
        ->toThrow(function (RateLimited $exception): void {
            expect($exception->retryAfterMilliseconds)->toBe(800)
                ->and($this->sleeper->slept)->toBe([1500]);
        });
    Http::assertSentCount(2);
});

it('describes a 429 that carries no ImageKit message', function (): void {
    config()->set('imagekit-client.http.retries', 0);
    Http::fake(['api.imagekit.io/*' => Http::response('', 429)]);

    expect(fn () => ImageKitClient::files()->delete('file_123'))
        ->toThrow(function (RateLimited $exception): void {
            expect($exception->getMessage())->toBe('ImageKit responded with HTTP 429.')
                ->and($exception->retryAfterMilliseconds)->toBe(0);
        });
});

it('treats a 429 without X-RateLimit-Reset as a zero wait', function (): void {
    config()->set('imagekit-client.http.retries', 1);
    Http::fake(['api.imagekit.io/*' => Http::sequence()->pushStatus(429)->push(null, 204)]);

    ImageKitClient::files()->delete('file_123');

    expect($this->sleeper->slept)->toBe([0]);
});

it('applies http.timeout to every request', function (): void {
    config()->set('imagekit-client.http.timeout', 12);
    Http::fake(function (Request $request, array $options): PromiseInterface {
        expect($options['timeout'])->toBe(12);

        return Http::response(null, 204);
    });

    ImageKitClient::files()->delete('file_123');

    Http::assertSentCount(1);
});
