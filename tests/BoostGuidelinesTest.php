<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/**
 * Laravel Boost loads resources/boost/guidelines/core.blade.php from every
 * installed package on `boost:install` and renders it as Blade. This test is
 * the guard that the file exists, renders, and names only things that exist.
 */
const GUIDELINE = __DIR__.'/../resources/boost/guidelines/core.blade.php';

function renderedGuideline(): string
{
    $source = file_get_contents(GUIDELINE);

    expect($source)->toBeString();

    return Blade::render((string) $source);
}

it('ships a Boost guideline that renders as Blade', function (): void {
    expect(GUIDELINE)->toBeFile();

    $rendered = renderedGuideline();

    expect($rendered)->not->toBeEmpty()
        ->and($rendered)->not->toContain('{{')
        ->and($rendered)->not->toContain('@verbatim')
        ->and($rendered)->not->toContain('@endverbatim');
});

it('names only classes, interfaces and enums that exist in this package', function (): void {
    preg_match_all('/Thecyrilcril\\\\ImageKitClient(?:\\\\[A-Z][A-Za-z]+)+/', renderedGuideline(), $matches);

    $named = array_unique($matches[0]);

    expect($named)->not->toBeEmpty();

    foreach ($named as $symbol) {
        expect(class_exists($symbol) || interface_exists($symbol) || enum_exists($symbol))
            ->toBeTrue(sprintf('The guideline names [%s], which does not exist.', $symbol));
    }
});

it('points at the real config file, facade, fake and area methods', function (): void {
    $rendered = renderedGuideline();

    expect(__DIR__.'/../config/imagekit-client.php')->toBeFile()
        ->and($rendered)->toContain('config/imagekit-client.php')
        ->and($rendered)->toContain('ImageKitClient::files()')
        ->and($rendered)->toContain('ImageKitClient::urls()')
        ->and($rendered)->toContain('ImageKitClient::fake()')
        ->and($rendered)->toContain('Thecyrilcril\ImageKitClient\Contracts\Client')
        ->and($rendered)->toContain('Thecyrilcril\ImageKitClient\Facades\ImageKitClient')
        ->and($rendered)->toContain('Thecyrilcril\ImageKitClient\Exceptions\ImageKitClientException')
        ->and($rendered)->toContain('Thecyrilcril\ImageKitClient\Exceptions\InvalidTransformation');
});

it('states the rules the package depends on', function (): void {
    $rendered = renderedGuideline();

    expect($rendered)->toContain('Http::fake()')
        ->and($rendered)->toContain('imagekit/imagekit')
        ->and($rendered)->toContain('api.imagekit.io')
        ->and($rendered)->toContain('tr:w-200')
        ->and($rendered)->toContain('IMAGEKIT_PRIVATE_KEY');
});
