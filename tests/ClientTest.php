<?php

declare(strict_types=1);

use Thecyrilcril\ImageKitClient\Contracts\Client;
use Thecyrilcril\ImageKitClient\Contracts\Files;
use Thecyrilcril\ImageKitClient\Contracts\Urls;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidConfiguration;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;

it('resolves the Client contract as a singleton', function (): void {
    $client = app(Client::class);

    expect($client)->toBeInstanceOf(Client::class)
        ->and(app(Client::class))->toBe($client);
});

it('resolves the same singleton through the facade and the container', function (): void {
    expect(ImageKitClient::getFacadeRoot())->toBe(app(Client::class));
});

it('exposes one interface per API area', function (): void {
    $client = app(Client::class);

    expect($client->files())->toBeInstanceOf(Files::class)
        ->and($client->urls())->toBeInstanceOf(Urls::class)
        ->and(ImageKitClient::files())->toBe($client->files())
        ->and(ImageKitClient::urls())->toBe($client->urls());
});

it('throws a configuration exception when a credential is missing', function (string $key, string $env): void {
    config()->set('imagekit-client.'.$key, null);

    expect(fn () => app(Client::class))
        ->toThrow(InvalidConfiguration::class, $env);
})->with([
    'public_key' => ['public_key', 'IMAGEKIT_PUBLIC_KEY'],
    'private_key' => ['private_key', 'IMAGEKIT_PRIVATE_KEY'],
    'url_endpoint' => ['url_endpoint', 'IMAGEKIT_URL_ENDPOINT'],
]);

it('treats an empty credential as missing', function (): void {
    config()->set('imagekit-client.private_key', '');

    expect(fn () => app(Client::class))
        ->toThrow(InvalidConfiguration::class, 'IMAGEKIT_PRIVATE_KEY');
});

it('resolves when every credential is present', function (): void {
    expect(app(Client::class))->toBeInstanceOf(Client::class);
});
