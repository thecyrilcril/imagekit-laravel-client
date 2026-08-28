<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Pest\Expectation;
use PHPUnit\Framework\AssertionFailedError;
use Thecyrilcril\ImageKitClient\Contracts\Client;
use Thecyrilcril\ImageKitClient\Contracts\Files;
use Thecyrilcril\ImageKitClient\Enums\AssetType;
use Thecyrilcril\ImageKitClient\Exceptions\RequestFailed;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;
use Thecyrilcril\ImageKitClient\Files\File;
use Thecyrilcril\ImageKitClient\Files\FileListing;
use Thecyrilcril\ImageKitClient\Files\Folder;
use Thecyrilcril\ImageKitClient\Files\ListRequest;
use Thecyrilcril\ImageKitClient\Files\UploadedFile;
use Thecyrilcril\ImageKitClient\Files\UploadRequest;
use Thecyrilcril\ImageKitClient\Files\UploadSource;
use Thecyrilcril\ImageKitClient\Testing\ClientFake;
use Thecyrilcril\ImageKitClient\Urls\UrlRequest;

beforeEach(function (): void {
    // Any request that leaves after fake() is a bug: Http::fake() catches it
    // and the assertNothingSent checks below prove none left.
    Http::fake();
});

it('swaps the Client binding for the fake in the container and the facade', function (): void {
    $fake = ImageKitClient::fake();

    expect($fake)->toBeInstanceOf(ClientFake::class)
        ->and(app(Client::class))->toBe($fake)
        ->and(ImageKitClient::getFacadeRoot())->toBe($fake)
        ->and(ImageKitClient::files())->toBe($fake->files());
});

it('swaps the files area too, so code that injects Contracts\\Files is faked as well', function (): void {
    $fake = ImageKitClient::fake();

    app(Files::class)->delete('file_123');

    expect(app(Files::class))->toBe($fake);
    $fake->assertDeleted('file_123');
    Http::assertNothingSent();
});

it('answers an upload with a synthetic typed result and no HTTP request', function (): void {
    ImageKitClient::fake();

    $uploaded = ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::bytes('raw-jpeg-bytes'),
        fileName: 'photo.jpg',
        folder: '/avatars',
        tags: ['profile'],
        isPrivateFile: true,
        isPublished: false,
        customCoordinates: '10,10,100,100',
        customMetadata: ['owner' => 7],
        description: 'A face',
    ));

    expect($uploaded)->toBeInstanceOf(UploadedFile::class)
        ->and($uploaded->fileId)->toBe('fake_1')
        ->and($uploaded->name)->toBe('photo.jpg')
        ->and($uploaded->filePath)->toBe('/avatars/photo.jpg')
        ->and($uploaded->url)->toBe('https://ik.imagekit.io/test/avatars/photo.jpg')
        ->and($uploaded->thumbnailUrl)->toBe('https://ik.imagekit.io/test/tr:n-ik_ml_thumbnail/avatars/photo.jpg')
        ->and($uploaded->fileType)->toBe('image')
        ->and($uploaded->size)->toBe(14)
        ->and($uploaded->tags)->toBe(['profile'])
        ->and($uploaded->isPrivateFile)->toBeTrue()
        ->and($uploaded->isPublished)->toBeFalse()
        ->and($uploaded->customCoordinates)->toBe('10,10,100,100')
        ->and($uploaded->customMetadata)->toBe(['owner' => 7])
        ->and($uploaded->description)->toBe('A face');

    Http::assertNothingSent();
});

it('reads a non-image name and a non-bytes source as ImageKit would', function (): void {
    ImageKitClient::fake();

    $uploaded = ImageKitClient::files()->upload(new UploadRequest(
        source: UploadSource::url('https://example.com/report.pdf'),
        fileName: 'report.pdf',
    ));

    expect($uploaded->fileId)->toBe('fake_1')
        ->and($uploaded->filePath)->toBe('/report.pdf')
        ->and($uploaded->fileType)->toBe('non-image')
        ->and($uploaded->size)->toBe(0)
        ->and($uploaded->tags)->toBe([])
        ->and($uploaded->customMetadata)->toBe([])
        ->and($uploaded->isPrivateFile)->toBeNull();
});

it('numbers each upload in turn', function (): void {
    ImageKitClient::fake();

    ImageKitClient::files()->upload(new UploadRequest(UploadSource::bytes('a'), 'a.jpg'));
    $second = ImageKitClient::files()->upload(new UploadRequest(UploadSource::bytes('b'), 'b.jpg'));

    expect($second->fileId)->toBe('fake_2');
});

it('passes assertUploaded for a file name or a matching request', function (): void {
    $fake = ImageKitClient::fake();

    ImageKitClient::files()->upload(new UploadRequest(UploadSource::bytes('a'), 'photo.jpg', folder: '/avatars'));

    $fake->assertUploaded('photo.jpg');
    $fake->assertUploaded(fn (UploadRequest $request): bool => $request->folder === '/avatars');
});

it('fails assertUploaded when no upload matches', function (): void {
    $fake = ImageKitClient::fake();

    ImageKitClient::files()->upload(new UploadRequest(UploadSource::bytes('a'), 'photo.jpg'));

    expect(fn () => $fake->assertUploaded('other.jpg'))
        ->toThrow(AssertionFailedError::class, 'Expected a file named [other.jpg] to have been uploaded to ImageKit.')
        ->and(fn () => $fake->assertUploaded(fn (UploadRequest $request): bool => $request->folder === '/avatars'))
        ->toThrow(AssertionFailedError::class, 'Expected an upload matching the callback to have been made to ImageKit.');
});

it('passes assertNotUploaded when no upload matches', function (): void {
    $fake = ImageKitClient::fake();

    ImageKitClient::files()->upload(new UploadRequest(UploadSource::bytes('a'), 'photo.jpg'));

    $fake->assertNotUploaded('other.jpg');
    $fake->assertNotUploaded(fn (UploadRequest $request): bool => $request->folder === '/avatars');
});

it('fails assertNotUploaded when an upload matches', function (): void {
    $fake = ImageKitClient::fake();

    ImageKitClient::files()->upload(new UploadRequest(UploadSource::bytes('a'), 'photo.jpg', folder: '/avatars'));

    expect(fn () => $fake->assertNotUploaded('photo.jpg'))
        ->toThrow(AssertionFailedError::class, 'Expected no file named [photo.jpg] to have been uploaded to ImageKit.')
        ->and(fn () => $fake->assertNotUploaded(fn (UploadRequest $request): bool => $request->folder === '/avatars'))
        ->toThrow(AssertionFailedError::class, 'Expected no upload matching the callback to have been made to ImageKit.');
});

it('passes assertNothingUploaded before any upload', function (): void {
    ImageKitClient::fake()->assertNothingUploaded();
});

it('fails assertNothingUploaded after an upload', function (): void {
    $fake = ImageKitClient::fake();

    ImageKitClient::files()->upload(new UploadRequest(UploadSource::bytes('a'), 'photo.jpg'));

    expect(fn () => $fake->assertNothingUploaded())
        ->toThrow(AssertionFailedError::class, 'Expected no uploads to ImageKit, but 1 was made.');
});

it('throws request-failed for every upload once told to fail, and still records it', function (): void {
    $fake = ImageKitClient::fake()->failUploads();

    expect(fn () => ImageKitClient::files()->upload(new UploadRequest(UploadSource::bytes('a'), 'photo.jpg')))
        ->toThrow(function (RequestFailed $exception): void {
            expect($exception->status)->toBe(500)
                ->and($exception->imageKitMessage)->toBe('ImageKitClient::fake() was told to fail uploads.')
                ->and($exception->help)->toBeNull();
        });

    $fake->assertUploaded('photo.jpg');

    $fake->failUploads(false);

    expect(ImageKitClient::files()->upload(new UploadRequest(UploadSource::bytes('a'), 'photo.jpg')))
        ->toBeInstanceOf(UploadedFile::class);

    Http::assertNothingSent();
});

function seededFile(string $filePath, AssetType $type = AssetType::File): File
{
    return new File(
        fileId: 'file_'.md5($filePath),
        type: $type,
        name: basename($filePath),
        filePath: $filePath,
        url: 'https://ik.imagekit.io/test'.$filePath,
        fileType: 'image',
        createdAt: new DateTimeImmutable('2026-08-01T10:00:00Z'),
        updatedAt: new DateTimeImmutable('2026-08-01T10:00:00Z'),
    );
}

function seededFolder(string $folderPath): Folder
{
    return new Folder(
        folderId: 'folder_'.md5($folderPath),
        name: basename($folderPath),
        folderPath: $folderPath,
        createdAt: new DateTimeImmutable('2026-08-01T10:00:00Z'),
        updatedAt: new DateTimeImmutable('2026-08-01T10:00:00Z'),
    );
}

it('records a deletion and sends no HTTP request', function (): void {
    $fake = ImageKitClient::fake();

    ImageKitClient::files()->delete('file_123');

    $fake->assertDeleted('file_123');
    Http::assertNothingSent();
});

it('fails assertDeleted when that file id was not deleted', function (): void {
    $fake = ImageKitClient::fake();

    ImageKitClient::files()->delete('file_123');

    expect(fn () => $fake->assertDeleted('file_999'))
        ->toThrow(AssertionFailedError::class, 'Expected ImageKit file [file_999] to have been deleted.');
});

it('answers an unseeded listing with an empty page and no HTTP request', function (): void {
    $fake = ImageKitClient::fake();

    $listing = ImageKitClient::files()->list(new ListRequest(path: '/avatars'));

    expect($listing)->toBeInstanceOf(FileListing::class)
        ->and($listing->isEmpty())->toBeTrue();

    $fake->assertListed('/avatars');
    Http::assertNothingSent();
});

it('returns seeded files, in order, for a bare listing', function (): void {
    $fake = ImageKitClient::fake()->seedListing(
        seededFile('/avatars/a.jpg'),
        seededFile('/b.jpg'),
    );

    $listing = ImageKitClient::files()->list(new ListRequest);

    expect(array_map(static fn (File|Folder $item): string => $item->name, $listing->items))
        ->toBe(['a.jpg', 'b.jpg']);

    $fake->assertListed(fn (ListRequest $request): bool => $request->path === null);
});

it('narrows a listing to one folder level by path', function (): void {
    ImageKitClient::fake()->seedListing(
        seededFile('/avatars/a.jpg'),
        seededFile('/avatars/2026/deep.jpg'),
        seededFile('/root.jpg'),
    );

    expect(ImageKitClient::files()->list(new ListRequest(path: '/avatars/'))->files())
        ->sequence(fn (Expectation $file) => $file->filePath->toBe('/avatars/a.jpg'))
        ->and(ImageKitClient::files()->list(new ListRequest(path: '/'))->files())
        ->sequence(fn (Expectation $file) => $file->filePath->toBe('/root.jpg'))
        ->and(ImageKitClient::files()->list(new ListRequest(path: 'avatars/2026'))->files())
        ->sequence(fn (Expectation $file) => $file->filePath->toBe('/avatars/2026/deep.jpg'));
});

it('narrows folders by path the same way, so a folder walk sees one level at a time', function (): void {
    ImageKitClient::fake()->seedListing(
        seededFolder('/avatars'),
        seededFolder('/avatars/2026'),
        seededFile('/avatars/a.jpg'),
    );

    $listing = ImageKitClient::files()->list(new ListRequest(path: '/avatars', type: AssetType::All));

    expect(array_map(static fn (File|Folder $item): string => $item->name, $listing->items))
        ->toBe(['2026', 'a.jpg']);
});

it('filters a listing by asset type as ImageKit does', function (?AssetType $type, array $expected): void {
    ImageKitClient::fake()->seedListing(
        seededFile('/a.jpg'),
        seededFile('/a.jpg', AssetType::FileVersion),
        seededFolder('/avatars'),
    );

    $listing = ImageKitClient::files()->list(new ListRequest(type: $type));

    expect(array_map(static fn (File|Folder $item): string => $item->type->value, $listing->items))
        ->toBe($expected);
})->with([
    'default is files' => [null, ['file']],
    'file' => [AssetType::File, ['file']],
    'file-version' => [AssetType::FileVersion, ['file-version']],
    'folder' => [AssetType::Folder, ['folder']],
    'all is files and folders' => [AssetType::All, ['file', 'folder']],
]);

it('pages a seeded listing by skip and limit', function (): void {
    ImageKitClient::fake()->seedListing(
        seededFile('/a.jpg'),
        seededFile('/b.jpg'),
        seededFile('/c.jpg'),
    );

    $page = ImageKitClient::files()->list(new ListRequest(limit: 2, skip: 1));

    expect(array_map(static fn (File|Folder $item): string => $item->name, $page->items))
        ->toBe(['b.jpg', 'c.jpg']);
});

it('walks every seeded item lazily, one page at a time', function (): void {
    $fake = ImageKitClient::fake()->seedListing(
        seededFile('/a.jpg'),
        seededFile('/b.jpg'),
        seededFile('/c.jpg'),
    );

    $names = ImageKitClient::files()->lazy(new ListRequest(limit: 2))
        ->map(static fn (File|Folder $item): string => $item->name)
        ->all();

    expect($names)->toBe(['a.jpg', 'b.jpg', 'c.jpg']);

    $fake->assertListed(fn (ListRequest $request): bool => $request->skip === 0 && $request->limit === 2);
    $fake->assertListed(fn (ListRequest $request): bool => $request->skip === 2 && $request->limit === 2);
    Http::assertNothingSent();
});

it('fails assertListed when no listing matches', function (): void {
    $fake = ImageKitClient::fake();

    ImageKitClient::files()->list(new ListRequest(path: '/avatars'));

    expect(fn () => $fake->assertListed('/other'))
        ->toThrow(AssertionFailedError::class, 'Expected a listing of path [/other] to have been requested from ImageKit.')
        ->and(fn () => $fake->assertListed(fn (ListRequest $request): bool => $request->limit === 5))
        ->toThrow(AssertionFailedError::class, 'Expected a listing matching the callback to have been requested from ImageKit.');
});

it('builds URLs through the real builder, signed ones included', function (): void {
    $requests = [
        new UrlRequest(path: '/avatars/photo.jpg', transformation: ['width' => 200, 'focus' => 'face']),
        new UrlRequest(path: '/private/photo.jpg', signed: true, expiresIn: 300),
        new UrlRequest(src: 'https://ik.imagekit.io/other/photo.jpg', transformation: [['height' => 100], ['radius' => 'max']]),
    ];

    $real = array_map(ImageKitClient::urls()->build(...), $requests);

    ImageKitClient::fake();

    expect(array_map(ImageKitClient::urls()->build(...), $requests))->toBe($real)
        ->and($real[0])->toBe('https://ik.imagekit.io/test/tr:w-200,fo-face/avatars/photo.jpg')
        ->and($real[1])->toContain('ik-t=1700000300', 'ik-s=');

    Http::assertNothingSent();
});
