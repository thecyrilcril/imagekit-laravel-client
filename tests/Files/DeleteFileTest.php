<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Thecyrilcril\ImageKitClient\Exceptions\ImageKitError;
use Thecyrilcril\ImageKitClient\Exceptions\NotFound;
use Thecyrilcril\ImageKitClient\Exceptions\RequestFailed;
use Thecyrilcril\ImageKitClient\Exceptions\TransportError;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;

it('sends an authenticated DELETE for the file id with no body', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response(null, 204)]);

    ImageKitClient::files()->delete('file_123');

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://api.imagekit.io/v1/files/file_123'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('private_test:'))
        && $request->hasHeader('Accept', 'application/json')
        && $request->body() === '');
});

it('throws not-found when ImageKit has no file with that id', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response([
        'message' => 'The requested file does not exist.',
        'help' => 'For support kindly contact us at support@imagekit.io .',
    ], 404)]);

    expect(fn () => ImageKitClient::files()->delete('missing'))
        ->toThrow(function (NotFound $exception): void {
            expect($exception)->toBeInstanceOf(ImageKitError::class)
                ->and($exception->status)->toBe(404)
                ->and($exception->imageKitMessage)->toBe('The requested file does not exist.')
                ->and($exception->help)->toBe('For support kindly contact us at support@imagekit.io .')
                ->and($exception->getMessage())->toBe('ImageKit responded with HTTP 404: The requested file does not exist.');
        });
});

it('throws request-failed with the status, message and help for other errors', function (int $status): void {
    Http::fake(['api.imagekit.io/*' => Http::response([
        'message' => 'Your request contains invalid fileId parameter.',
        'help' => 'For support kindly contact us at support@imagekit.io .',
    ], $status)]);

    expect(fn () => ImageKitClient::files()->delete('bad id'))
        ->toThrow(function (RequestFailed $exception) use ($status): void {
            expect($exception->status)->toBe($status)
                ->and($exception->imageKitMessage)->toBe('Your request contains invalid fileId parameter.')
                ->and($exception->help)->toBe('For support kindly contact us at support@imagekit.io .')
                ->and($exception->getMessage())->toBe(sprintf('ImageKit responded with HTTP %d: Your request contains invalid fileId parameter.', $status));
        });
})->with([
    'bad request' => [400],
    'unauthorized' => [401],
    'server error' => [500],
]);

it('describes a failure that carries no ImageKit message', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response('<html>Bad Gateway</html>', 502)]);

    expect(fn () => ImageKitClient::files()->delete('file_123'))
        ->toThrow(function (RequestFailed $exception): void {
            expect($exception->status)->toBe(502)
                ->and($exception->imageKitMessage)->toBeNull()
                ->and($exception->help)->toBeNull()
                ->and($exception->getMessage())->toBe('ImageKit responded with HTTP 502.');
        });
});

it('throws transport when ImageKit cannot be reached', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::failedConnection()]);

    expect(fn () => ImageKitClient::files()->delete('file_123'))
        ->toThrow(function (TransportError $exception): void {
            expect($exception->getPrevious())->toBeInstanceOf(ConnectionException::class)
                ->and($exception->getMessage())->toContain('Could not resolve host: api.imagekit.io');
        });
});
