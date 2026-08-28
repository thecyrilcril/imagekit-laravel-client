<?php

declare(strict_types=1);

use Thecyrilcril\ImageKitClient\Configuration;
use Thecyrilcril\ImageKitClient\Enums\TransformationPosition;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidConfiguration;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validConfiguration(array $overrides = []): array
{
    return array_replace_recursive([
        'public_key' => 'public_test',
        'private_key' => 'private_test',
        'url_endpoint' => 'https://ik.imagekit.io/test',
        'transformation_position' => 'path',
        'http' => ['timeout' => 30, 'retries' => 0],
    ], $overrides);
}

it('reads every key into a typed configuration', function (): void {
    $configuration = Configuration::fromArray(validConfiguration([
        'transformation_position' => 'query',
        'http' => ['timeout' => 10, 'retries' => 3],
    ]));

    expect($configuration->publicKey)->toBe('public_test')
        ->and($configuration->privateKey)->toBe('private_test')
        ->and($configuration->urlEndpoint)->toBe('https://ik.imagekit.io/test')
        ->and($configuration->transformationPosition)->toBe(TransformationPosition::Query)
        ->and($configuration->timeout)->toBe(10)
        ->and($configuration->retries)->toBe(3);
});

it('casts numeric strings from the environment to integers', function (): void {
    $configuration = Configuration::fromArray(validConfiguration([
        'http' => ['timeout' => '45', 'retries' => '2'],
    ]));

    expect($configuration->timeout)->toBe(45)
        ->and($configuration->retries)->toBe(2);
});

it('falls back to the defaults when optional keys are absent', function (): void {
    $configuration = Configuration::fromArray([
        'public_key' => 'public_test',
        'private_key' => 'private_test',
        'url_endpoint' => 'https://ik.imagekit.io/test',
    ]);

    expect($configuration->transformationPosition)->toBe(TransformationPosition::Path)
        ->and($configuration->timeout)->toBe(30)
        ->and($configuration->retries)->toBe(0);
});

it('rejects an unknown transformation position', function (mixed $value): void {
    expect(fn () => Configuration::fromArray(validConfiguration(['transformation_position' => $value])))
        ->toThrow(InvalidConfiguration::class, 'transformation_position');
})->with([
    'unknown word' => ['sideways'],
    'not a string' => [true],
]);

it('rejects a timeout or retry count that is not a whole number', function (string $key, mixed $value): void {
    expect(fn () => Configuration::fromArray(validConfiguration(['http' => [$key => $value]])))
        ->toThrow(InvalidConfiguration::class, 'http.'.$key);
})->with([
    'timeout word' => ['timeout', 'soon'],
    'timeout negative' => ['timeout', -1],
    'timeout fraction' => ['timeout', 1.5],
    'retries array' => ['retries', []],
    'retries negative string' => ['retries', '-2'],
]);
