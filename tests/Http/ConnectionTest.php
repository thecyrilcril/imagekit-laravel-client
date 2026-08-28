<?php

declare(strict_types=1);

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Thecyrilcril\ImageKitClient\Http\Connection;

it('sends upload requests to the upload host with the same auth', function (): void {
    Http::fake(['upload.imagekit.io/*' => Http::response(['fileId' => 'f1'], 200)]);

    $response = app(Connection::class)->upload(
        fn (PendingRequest $request): Response => $request->post('/files/upload', ['fileName' => 'a.jpg']),
    );

    expect($response->json('fileId'))->toBe('f1');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://upload.imagekit.io/api/v1/files/upload'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('private_test:')));
});
