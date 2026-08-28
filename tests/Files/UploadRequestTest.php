<?php

declare(strict_types=1);

use Thecyrilcril\ImageKitClient\Enums\UploadSourceKind;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidUploadRequest;
use Thecyrilcril\ImageKitClient\Files\UploadRequest;
use Thecyrilcril\ImageKitClient\Files\UploadSource;

it('names the kind of each source and keeps its value verbatim', function (UploadSource $source, UploadSourceKind $kind, string $value): void {
    expect($source->kind)->toBe($kind)
        ->and($source->value)->toBe($value);
})->with([
    'bytes' => [fn (): UploadSource => UploadSource::bytes('raw'), UploadSourceKind::Bytes, 'raw'],
    'data uri' => [fn (): UploadSource => UploadSource::dataUri('data:image/png;base64,iVBORw0KGgo='), UploadSourceKind::DataUri, 'data:image/png;base64,iVBORw0KGgo='],
    'https url' => [fn (): UploadSource => UploadSource::url('https://example.com/a.jpg'), UploadSourceKind::Url, 'https://example.com/a.jpg'],
    'http url' => [fn (): UploadSource => UploadSource::url('http://example.com/a.jpg'), UploadSourceKind::Url, 'http://example.com/a.jpg'],
]);

it('rejects empty bytes', function (): void {
    expect(fn () => UploadSource::bytes(''))
        ->toThrow(InvalidUploadRequest::class, 'no bytes');
});

it('rejects a data URI that does not start with data:', function (): void {
    expect(fn () => UploadSource::dataUri('iVBORw0KGgo='))
        ->toThrow(InvalidUploadRequest::class, 'must start with "data:"');
});

it('rejects a URL that is not http or https', function (string $url): void {
    expect(fn () => UploadSource::url($url))
        ->toThrow(InvalidUploadRequest::class, 'must be an http:// or https:// URL');
})->with([
    'ftp scheme' => ['ftp://example.com/a.jpg'],
    'no scheme' => ['example.com/a.jpg'],
    'empty' => [''],
]);

it('rejects an empty file name', function (): void {
    expect(fn () => new UploadRequest(source: UploadSource::bytes('raw'), fileName: ''))
        ->toThrow(InvalidUploadRequest::class, 'fileName');
});
