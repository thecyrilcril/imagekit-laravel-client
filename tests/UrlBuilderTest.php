<?php

declare(strict_types=1);

use Thecyrilcril\ImageKitClient\Contracts\Client;
use Thecyrilcril\ImageKitClient\Enums\TransformationPosition;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidTransformation;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidUrlRequest;
use Thecyrilcril\ImageKitClient\Urls\UrlRequest;

/**
 * @param  array<string, mixed>|list<array<string, mixed>>  $transformation
 */
function buildUrl(array $transformation = [], string $path = '/a.jpg'): string
{
    return app(Client::class)->urls()->build(new UrlRequest(path: $path, transformation: $transformation));
}

it('renders friendly keys as short codes in the URL path', function (): void {
    expect(buildUrl(['width' => 200, 'height' => 200, 'focus' => 'face']))
        ->toBe('https://ik.imagekit.io/test/tr:w-200,h-200,fo-face/a.jpg');
});

it('returns the plain URL when the Transformation is empty', function (): void {
    expect(buildUrl([]))->toBe('https://ik.imagekit.io/test/a.jpg');
});

it('renders every alias as its short code', function (string $alias, mixed $value, string $expected): void {
    expect(buildUrl([$alias => $value]))->toBe('https://ik.imagekit.io/test/tr:'.$expected.'/a.jpg');
})->with([
    'width' => ['width', 200, 'w-200'],
    'height' => ['height', 200, 'h-200'],
    'aspectRatio' => ['aspectRatio', '4-3', 'ar-4-3'],
    'crop' => ['crop', 'at_max', 'c-at_max'],
    'cropMode' => ['cropMode', 'pad_resize', 'cm-pad_resize'],
    'focus' => ['focus', 'face', 'fo-face'],
    'zoom' => ['zoom', 0.5, 'z-0.5'],
    'x' => ['x', 10, 'x-10'],
    'y' => ['y', 20, 'y-20'],
    'xCenter' => ['xCenter', 100, 'xc-100'],
    'yCenter' => ['yCenter', 120, 'yc-120'],
    'dpr' => ['dpr', 2, 'dpr-2'],
    'quality' => ['quality', 80, 'q-80'],
    'format' => ['format', 'webp', 'f-webp'],
    'lossless' => ['lossless', true, 'lo-true'],
    'progressive' => ['progressive', true, 'pr-true'],
    'metadata' => ['metadata', true, 'md-true'],
    'colorProfile' => ['colorProfile', true, 'cp-true'],
    'density' => ['density', 300, 'dn-300'],
    'original' => ['original', true, 'orig-true'],
    'defaultImage' => ['defaultImage', 'fallback.jpg', 'di-fallback.jpg'],
    'named' => ['named', 'thumb', 'n-thumb'],
    'radius' => ['radius', 'max', 'r-max'],
    'background' => ['background', 'FF0000', 'bg-FF0000'],
    'border' => ['border', '5_FFF000', 'b-5_FFF000'],
    'rotation' => ['rotation', 90, 'rt-90'],
    'flip' => ['flip', 'h', 'fl-h'],
    'blur' => ['blur', 10, 'bl-10'],
    'trim' => ['trim', 5, 't-5'],
    'opacity' => ['opacity', 50, 'o-50'],
    'colorReplace' => ['colorReplace', '065465_40', 'cr-065465_40'],
    'contrastStretch' => ['contrastStretch', true, 'e-contrast'],
    'sharpen' => ['sharpen', 10, 'e-sharpen-10'],
    'unsharpMask' => ['unsharpMask', '2-2-0.8-0.024', 'e-usm-2-2-0.8-0.024'],
    'grayscale' => ['grayscale', true, 'e-grayscale'],
    'shadow' => ['shadow', 'bl-15_st-40_x-10_y-N5', 'e-shadow-bl-15_st-40_x-10_y-N5'],
    'gradient' => ['gradient', 'ld-45_from-FF000050_to-FFFFFF50_sp-0.5', 'e-gradient-ld-45_from-FF000050_to-FFFFFF50_sp-0.5'],
    'colorize' => ['colorize', 'co-FF0000_in-15', 'e-colorize-co-FF0000_in-15'],
    'distort' => ['distort', 'a-30', 'e-distort-a-30'],
    'aiRemoveBackground' => ['aiRemoveBackground', true, 'e-bgremove'],
    'aiRemoveBackgroundExternal' => ['aiRemoveBackgroundExternal', true, 'e-removedotbg'],
    'aiChangeBackground' => ['aiChangeBackground', 'prompt-snow', 'e-changebg-prompt-snow'],
    'aiEdit' => ['aiEdit', 'prompte-bWFrZQ', 'e-edit-prompte-bWFrZQ'],
    'aiDropShadow' => ['aiDropShadow', 'az-45', 'e-dropshadow-az-45'],
    'aiRetouch' => ['aiRetouch', true, 'e-retouch'],
    'aiUpscale' => ['aiUpscale', true, 'e-upscale'],
    'aiVariation' => ['aiVariation', true, 'e-genvar'],
    'page' => ['page', '1_3_5', 'pg-1_3_5'],
    'contentCredentials' => ['contentCredentials', true, 'c2pa-true'],
    'startOffset' => ['startOffset', 5, 'so-5'],
    'endOffset' => ['endOffset', 15, 'eo-15'],
    'duration' => ['duration', 10, 'du-10'],
    'videoCodec' => ['videoCodec', 'h264', 'vc-h264'],
    'audioCodec' => ['audioCodec', 'aac', 'ac-aac'],
    'streamingResolutions' => ['streamingResolutions', '240_360_480', 'sr-240_360_480'],
    // Names the imagekit/imagekit SDK 4.0.2 accepted, kept so Presets written
    // against it keep rendering byte-identical URLs.
    'rotate (legacy)' => ['rotate', 90, 'rt-90'],
    'effectSharpen (legacy)' => ['effectSharpen', 10, 'e-sharpen-10'],
    'effectUSM (legacy)' => ['effectUSM', '2-2-0.8-0.024', 'e-usm-2-2-0.8-0.024'],
    'effectContrast (legacy)' => ['effectContrast', true, 'e-contrast'],
    'effectGray (legacy)' => ['effectGray', true, 'e-grayscale'],
    'effectShadow (legacy)' => ['effectShadow', 'bl-15', 'e-shadow-bl-15'],
    'effectGradient (legacy)' => ['effectGradient', 'ld-45', 'e-gradient-ld-45'],
]);

it('accepts an ImageKit short code as a key', function (): void {
    expect(buildUrl(['w' => 200, 'e-bgremove' => true]))
        ->toBe('https://ik.imagekit.io/test/tr:w-200,e-bgremove/a.jpg');
});

it('passes a raw Transformation through verbatim, without encoding it', function (): void {
    expect(buildUrl(['raw' => 'l-text,i-Hello%20World,pg-name-"layer",l-end', 'width' => 200]))
        ->toBe('https://ik.imagekit.io/test/tr:l-text,i-Hello%20World,pg-name-"layer",l-end,w-200/a.jpg');
});

it('throws on an unknown Transformation key', function (): void {
    expect(fn () => buildUrl(['widht' => 200]))
        ->toThrow(InvalidTransformation::class, '[widht]');
});

it('spells false out for codes that take a boolean', function (): void {
    expect(buildUrl(['metadata' => false, 'colorProfile' => false]))
        ->toBe('https://ik.imagekit.io/test/tr:md-false,cp-false/a.jpg');
});

it('renders a dash or empty value as the bare code, as the SDK did', function (string $value): void {
    expect(buildUrl(['effectSharpen' => $value]))
        ->toBe('https://ik.imagekit.io/test/tr:e-sharpen/a.jpg');
})->with(['dash' => ['-'], 'empty' => ['']]);

it('encodes a slash inside a value as @@', function (): void {
    expect(buildUrl(['defaultImage' => '/images/fallback.jpg']))
        ->toBe('https://ik.imagekit.io/test/tr:di-images@@fallback.jpg/a.jpg');
});

it('throws when a value is not a scalar', function (): void {
    expect(fn () => buildUrl(['width' => [200]]))
        ->toThrow(InvalidTransformation::class, '[width]');
});

it('joins a chain of Transformations with a colon', function (): void {
    expect(buildUrl([['width' => 400, 'height' => 300], ['rotation' => 90]]))
        ->toBe('https://ik.imagekit.io/test/tr:w-400,h-300:rt-90/a.jpg');
});

it('skips empty steps in a chain', function (): void {
    expect(buildUrl([['width' => 400], []]))
        ->toBe('https://ik.imagekit.io/test/tr:w-400/a.jpg');
});

it('percent-encodes a Transformation value that is not URL safe', function (): void {
    expect(buildUrl(['aiChangeBackground' => 'prompt-snow road', 'aiEdit' => 'prompte-bWFrZQ==']))
        ->toBe('https://ik.imagekit.io/test/tr:e-changebg-prompt-snow%20road,e-edit-prompte-bWFrZQ%3D%3D/a.jpg');
});

it('puts the Transformation in the query string when the request asks', function (): void {
    $url = app(Client::class)->urls()->build(new UrlRequest(
        path: '/a.jpg',
        transformation: ['width' => 200],
        position: TransformationPosition::Query,
    ));

    expect($url)->toBe('https://ik.imagekit.io/test/a.jpg?tr=w-200');
});

it('reads the default Transformation position from config', function (): void {
    config()->set('imagekit-client.transformation_position', 'query');

    expect(buildUrl(['width' => 200]))->toBe('https://ik.imagekit.io/test/a.jpg?tr=w-200');
});

it('lets the request override the configured position', function (): void {
    config()->set('imagekit-client.transformation_position', 'query');

    $url = app(Client::class)->urls()->build(new UrlRequest(
        path: '/a.jpg',
        transformation: ['width' => 200],
        position: TransformationPosition::Path,
    ));

    expect($url)->toBe('https://ik.imagekit.io/test/tr:w-200/a.jpg');
});

it('builds from an absolute src with the Transformation in the query string', function (): void {
    $url = app(Client::class)->urls()->build(new UrlRequest(
        src: 'https://ik.imagekit.io/other/b.jpg',
        transformation: ['width' => 200],
        position: TransformationPosition::Path,
    ));

    expect($url)->toBe('https://ik.imagekit.io/other/b.jpg?tr=w-200');
});

it('keeps the query string a src already carries', function (): void {
    $url = app(Client::class)->urls()->build(new UrlRequest(
        src: 'https://ik.imagekit.io/other/b.jpg?v=2',
        transformation: ['width' => 200],
    ));

    expect($url)->toBe('https://ik.imagekit.io/other/b.jpg?v=2&tr=w-200');
});

it('returns a src untouched when there is nothing to add', function (): void {
    $url = app(Client::class)->urls()->build(new UrlRequest(src: 'https://ik.imagekit.io/other/b.jpg'));

    expect($url)->toBe('https://ik.imagekit.io/other/b.jpg');
});

it('appends extra query parameters after a path Transformation', function (): void {
    $url = app(Client::class)->urls()->build(new UrlRequest(
        path: '/a.jpg',
        transformation: ['width' => 200],
        queryParameters: ['ik-attachment' => true, 'ik-attachment-filename' => 'my photo.jpg'],
    ));

    expect($url)->toBe('https://ik.imagekit.io/test/tr:w-200/a.jpg?ik-attachment=true&ik-attachment-filename=my%20photo.jpg');
});

it('appends extra query parameters after a query Transformation', function (): void {
    $url = app(Client::class)->urls()->build(new UrlRequest(
        path: '/a.jpg',
        transformation: ['width' => 200],
        position: TransformationPosition::Query,
        queryParameters: ['ik-sanitizeSvg' => false, 'v' => 3],
    ));

    expect($url)->toBe('https://ik.imagekit.io/test/a.jpg?tr=w-200&ik-sanitizeSvg=false&v=3');
});

it('appends extra query parameters without a Transformation', function (): void {
    $url = app(Client::class)->urls()->build(new UrlRequest(path: 'a.jpg', queryParameters: ['v' => 3]));

    expect($url)->toBe('https://ik.imagekit.io/test/a.jpg?v=3');
});

it('rejects a request with neither path nor src', function (): void {
    expect(fn () => new UrlRequest)->toThrow(InvalidUrlRequest::class, 'neither');
});

it('rejects a request with both path and src', function (): void {
    expect(fn () => new UrlRequest(path: '/a.jpg', src: 'https://ik.imagekit.io/other/b.jpg'))
        ->toThrow(InvalidUrlRequest::class, 'not both');
});
