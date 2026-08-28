<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Thecyrilcril\ImageKitClient\Enums\AssetType;
use Thecyrilcril\ImageKitClient\Enums\FileType;
use Thecyrilcril\ImageKitClient\Enums\SortOrder;
use Thecyrilcril\ImageKitClient\Exceptions\RequestFailed;
use Thecyrilcril\ImageKitClient\Exceptions\TransportError;
use Thecyrilcril\ImageKitClient\Exceptions\UnexpectedResponse;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;
use Thecyrilcril\ImageKitClient\Files\File;
use Thecyrilcril\ImageKitClient\Files\Folder;
use Thecyrilcril\ImageKitClient\Files\ListRequest;

it('sends an authenticated GET /files with every filter as a documented query parameter', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response([], 200)]);

    ImageKitClient::files()->list(new ListRequest(
        limit: 50,
        skip: 100,
        path: '/kitwire/avatars',
        type: AssetType::All,
        fileType: FileType::Image,
        sort: SortOrder::NameAscending,
        tags: ['hero', 'summer sale'],
        name: 'banner.jpg',
        searchQuery: 'createdAt > "7d" AND path : "/kitwire/"',
    ));

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api.imagekit.io/v1/files'
            .'?limit=50'
            .'&skip=100'
            .'&path=%2Fkitwire%2Favatars'
            .'&type=all'
            .'&fileType=image'
            .'&sort=ASC_NAME'
            .'&tags=hero%2Csummer%20sale'
            .'&name=banner.jpg'
            .'&searchQuery=createdAt%20%3E%20%227d%22%20AND%20path%20%3A%20%22%2Fkitwire%2F%22'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('private_test:'))
        && $request->hasHeader('Accept', 'application/json'));
});

it('sends no query string when the request carries no filter', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response([], 200)]);

    ImageKitClient::files()->list(new ListRequest);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.imagekit.io/v1/files');
});

it('yields typed File and Folder objects from a type=all page, told apart by type', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response([
        [
            'type' => 'file',
            'fileId' => '598821f949c0a938d57563bd',
            'name' => 'hero.png',
            'filePath' => '/kitwire/avatars/hero.png',
            'url' => 'https://ik.imagekit.io/test/kitwire/avatars/hero.png',
            'thumbnail' => 'https://ik.imagekit.io/test/tr:n-ik_ml_thumbnail/kitwire/avatars/hero.png',
            'fileType' => 'image',
            'mime' => 'image/png',
            'size' => 42_048,
            'width' => 3000,
            'height' => 2000,
            'hasAlpha' => true,
            'tags' => ['hero', 'summer sale'],
            'AITags' => [
                ['name' => 'Beach', 'confidence' => 98.11, 'source' => 'google-auto-tagging'],
            ],
            'customCoordinates' => '10,10,100,100',
            'customMetadata' => ['brand' => 'kitwire', 'priority' => 3],
            'description' => 'The landing page hero.',
            'embeddedMetadata' => ['XResolution' => 72],
            'selectedFieldsSchema' => ['brand' => ['type' => 'Text']],
            'isPrivateFile' => false,
            'isPublished' => true,
            'versionInfo' => ['id' => '598821f949c0a938d57563bd', 'name' => 'Version 1'],
            'createdAt' => '2026-08-01T10:00:00.123Z',
            'updatedAt' => '2026-08-02T11:30:00.000Z',
            'unknownFutureField' => 'ignored',
        ],
        [
            'type' => 'file-version',
            'fileId' => '6a1b2c3d4e5f60718293a4b5',
            'name' => 'intro.mp4',
            'filePath' => '/kitwire/videos/intro.mp4',
            'url' => 'https://ik.imagekit.io/test/kitwire/videos/intro.mp4?ik-obj-version=abc',
            'fileType' => 'non-image',
            'mime' => 'video/mp4',
            'size' => 8_388_608,
            'tags' => null,
            'AITags' => null,
            'customCoordinates' => null,
            'isPrivateFile' => true,
            'isPublished' => false,
            'duration' => 12,
            'bitRate' => 1_200,
            'audioCodec' => 'aac',
            'videoCodec' => 'h264',
            'versionInfo' => ['id' => 'abc', 'name' => 'Version 2'],
            'createdAt' => '2026-08-03T00:00:00.000Z',
            'updatedAt' => '2026-08-03T00:00:00.000Z',
        ],
        [
            'type' => 'folder',
            'folderId' => '5f2a1b3c4d5e6f7081920a1b',
            'name' => 'thumbnails',
            'folderPath' => '/kitwire/avatars/thumbnails',
            'customMetadata' => [],
            'createdAt' => '2026-07-01T00:00:00.000Z',
            'updatedAt' => '2026-07-15T00:00:00.000Z',
        ],
    ], 200)]);

    $listing = ImageKitClient::files()->list(new ListRequest(path: '/kitwire/avatars', type: AssetType::All));

    expect($listing)->toHaveCount(3)
        ->and($listing->isEmpty())->toBeFalse()
        ->and(iterator_to_array($listing))->toBe($listing->items)
        ->and($listing->files())->toHaveCount(2)
        ->and($listing->folders())->toHaveCount(1);

    [$image, $video, $folder] = $listing->items;

    expect($image)->toBeInstanceOf(File::class)
        ->and($image->type)->toBe(AssetType::File)
        ->and($image->fileId)->toBe('598821f949c0a938d57563bd')
        ->and($image->name)->toBe('hero.png')
        ->and($image->filePath)->toBe('/kitwire/avatars/hero.png')
        ->and($image->url)->toBe('https://ik.imagekit.io/test/kitwire/avatars/hero.png')
        ->and($image->thumbnail)->toBe('https://ik.imagekit.io/test/tr:n-ik_ml_thumbnail/kitwire/avatars/hero.png')
        ->and($image->fileType)->toBe('image')
        ->and($image->mime)->toBe('image/png')
        ->and($image->size)->toBe(42_048)
        ->and($image->width)->toBe(3000)
        ->and($image->height)->toBe(2000)
        ->and($image->hasAlpha)->toBeTrue()
        ->and($image->tags)->toBe(['hero', 'summer sale'])
        ->and($image->aiTags)->toHaveCount(1)
        ->and($image->aiTags[0]->name)->toBe('Beach')
        ->and($image->aiTags[0]->confidence)->toBe(98.11)
        ->and($image->aiTags[0]->source)->toBe('google-auto-tagging')
        ->and($image->customCoordinates)->toBe('10,10,100,100')
        ->and($image->customMetadata)->toBe(['brand' => 'kitwire', 'priority' => 3])
        ->and($image->description)->toBe('The landing page hero.')
        ->and($image->embeddedMetadata)->toBe(['XResolution' => 72])
        ->and($image->selectedFieldsSchema)->toBe(['brand' => ['type' => 'Text']])
        ->and($image->isPrivateFile)->toBeFalse()
        ->and($image->isPublished)->toBeTrue()
        ->and($image->versionInfo?->id)->toBe('598821f949c0a938d57563bd')
        ->and($image->versionInfo?->name)->toBe('Version 1')
        ->and($image->createdAt->format(DATE_RFC3339_EXTENDED))->toBe('2026-08-01T10:00:00.123+00:00')
        ->and($image->updatedAt->format(DATE_RFC3339_EXTENDED))->toBe('2026-08-02T11:30:00.000+00:00')
        ->and($image->duration)->toBeNull()
        ->and($image->bitRate)->toBeNull()
        ->and($image->audioCodec)->toBeNull()
        ->and($image->videoCodec)->toBeNull();

    expect($video)->toBeInstanceOf(File::class)
        ->and($video->type)->toBe(AssetType::FileVersion)
        ->and($video->fileType)->toBe('non-image')
        ->and($video->thumbnail)->toBeNull()
        ->and($video->width)->toBeNull()
        ->and($video->height)->toBeNull()
        ->and($video->hasAlpha)->toBeNull()
        ->and($video->tags)->toBe([])
        ->and($video->aiTags)->toBe([])
        ->and($video->customCoordinates)->toBeNull()
        ->and($video->customMetadata)->toBe([])
        ->and($video->description)->toBeNull()
        ->and($video->embeddedMetadata)->toBe([])
        ->and($video->selectedFieldsSchema)->toBe([])
        ->and($video->isPrivateFile)->toBeTrue()
        ->and($video->isPublished)->toBeFalse()
        ->and($video->versionInfo?->name)->toBe('Version 2')
        ->and($video->duration)->toBe(12)
        ->and($video->bitRate)->toBe(1_200)
        ->and($video->audioCodec)->toBe('aac')
        ->and($video->videoCodec)->toBe('h264');

    expect($folder)->toBeInstanceOf(Folder::class)
        ->and($folder->type)->toBe(AssetType::Folder)
        ->and($folder->folderId)->toBe('5f2a1b3c4d5e6f7081920a1b')
        ->and($folder->name)->toBe('thumbnails')
        ->and($folder->folderPath)->toBe('/kitwire/avatars/thumbnails')
        ->and($folder->customMetadata)->toBe([])
        ->and($folder->createdAt->format(DATE_ATOM))->toBe('2026-07-01T00:00:00+00:00')
        ->and($folder->updatedAt->format(DATE_ATOM))->toBe('2026-07-15T00:00:00+00:00');
});

it('reads a file with only the always-present fields, defaulting the rest', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response([[
        'type' => 'file',
        'fileId' => 'f1',
        'name' => 'a.pdf',
        'filePath' => '/a.pdf',
        'url' => 'https://ik.imagekit.io/test/a.pdf',
        'fileType' => 'non-image',
        'createdAt' => '2026-08-03T00:00:00.000Z',
        'updatedAt' => '2026-08-03T00:00:00.000Z',
        // Wrong-typed optional fields read as absent rather than failing the page.
        'size' => 'big',
        'tags' => ['ok', 7, null],
        'AITags' => ['not an object'],
        'customMetadata' => 'nope',
        'versionInfo' => 'nope',
        'isPrivateFile' => 'yes',
    ]], 200)]);

    $file = ImageKitClient::files()->list(new ListRequest)->files()[0];

    expect($file->size)->toBeNull()
        ->and($file->tags)->toBe(['ok'])
        ->and($file->aiTags)->toBe([])
        ->and($file->customMetadata)->toBe([])
        ->and($file->versionInfo)->toBeNull()
        ->and($file->isPrivateFile)->toBeFalse()
        ->and($file->isPublished)->toBeTrue();
});

it('reads a whole-number float size as an int, and AI tags with no confidence or source', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response([[
        'type' => 'file',
        'fileId' => 'f1',
        'name' => 'a.jpg',
        'filePath' => '/a.jpg',
        'url' => 'https://ik.imagekit.io/test/a.jpg',
        'fileType' => 'image',
        'createdAt' => '2026-08-03T00:00:00.000Z',
        'updatedAt' => '2026-08-03T00:00:00.000Z',
        'size' => 1024.0,
        'AITags' => [['name' => 'Sky', 'confidence' => 90]],
    ]], 200)]);

    $file = ImageKitClient::files()->list(new ListRequest)->files()[0];

    expect($file->size)->toBe(1024)
        ->and($file->aiTags[0]->name)->toBe('Sky')
        ->and($file->aiTags[0]->confidence)->toBe(90.0)
        ->and($file->aiTags[0]->source)->toBeNull();
});

it('yields an empty listing, not an exception, when nothing matches', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response([], 200)]);

    $listing = ImageKitClient::files()->list(new ListRequest(path: '/nothing-here'));

    expect($listing)->toHaveCount(0)
        ->and($listing->isEmpty())->toBeTrue()
        ->and($listing->items)->toBe([])
        ->and($listing->files())->toBe([])
        ->and($listing->folders())->toBe([]);
});

it('throws unexpected-response when a 2xx body is not a listing', function (mixed $body, string $message): void {
    Http::fake(['api.imagekit.io/*' => Http::response($body, 200)]);

    expect(fn () => ImageKitClient::files()->list(new ListRequest))
        ->toThrow(UnexpectedResponse::class, $message);
})->with([
    'not JSON' => ['<html>OK</html>', 'ImageKit answered with a body that is not a JSON array of assets.'],
    'an object' => [['message' => 'hi'], 'ImageKit answered with a body that is not a JSON array of assets.'],
    'a scalar item' => [['file'], 'Asset #0 in the listing is not a JSON object.'],
    'no type' => [[['fileId' => 'f1']], 'Asset has an unknown type null; expected file, file-version or folder.'],
    'unknown type' => [[['type' => 'alias']], 'Asset has an unknown type "alias"; expected file, file-version or folder.'],
    'numeric type' => [[['type' => 7]], 'Asset has an unknown type 7; expected file, file-version or folder.'],
    'object type' => [[['type' => ['file']]], 'Asset has an unknown type array; expected file, file-version or folder.'],
    'file missing a required field' => [[['type' => 'file', 'fileId' => 'f1']], 'File is missing the required field "name".'],
    'file with a malformed required field' => [[['type' => 'file', 'fileId' => 42]], 'File field "fileId" is not a string.'],
    'folder missing a required field' => [[['type' => 'folder', 'folderId' => 'd1', 'name' => 'x']], 'Folder is missing the required field "folderPath".'],
    'bad date' => [[[
        'type' => 'folder', 'folderId' => 'd1', 'name' => 'x', 'folderPath' => '/x',
        'createdAt' => 'yesterday-ish', 'updatedAt' => '2026-07-15T00:00:00.000Z',
    ]], 'Folder field "createdAt" is not an ISO 8601 date-time.'],
    'relative date' => [[[
        'type' => 'folder', 'folderId' => 'd1', 'name' => 'x', 'folderPath' => '/x',
        'createdAt' => 'yesterday', 'updatedAt' => '2026-07-15T00:00:00.000Z',
    ]], 'Folder field "createdAt" is not an ISO 8601 date-time.'],
    'empty date' => [[[
        'type' => 'folder', 'folderId' => 'd1', 'name' => 'x', 'folderPath' => '/x',
        'createdAt' => '2026-07-01T00:00:00.000Z', 'updatedAt' => '',
    ]], 'Folder field "updatedAt" is not an ISO 8601 date-time.'],
    'impossible date' => [[[
        'type' => 'folder', 'folderId' => 'd1', 'name' => 'x', 'folderPath' => '/x',
        'createdAt' => '2026-13-45T99:00:00.000Z', 'updatedAt' => '2026-07-15T00:00:00.000Z',
    ]], 'Folder field "createdAt" is not an ISO 8601 date-time.'],
    'version info missing its id' => [[[
        'type' => 'file', 'fileId' => 'f1', 'name' => 'a.jpg', 'filePath' => '/a.jpg', 'url' => 'https://ik.imagekit.io/test/a.jpg',
        'fileType' => 'image', 'createdAt' => '2026-08-03T00:00:00.000Z', 'updatedAt' => '2026-08-03T00:00:00.000Z',
        'versionInfo' => ['name' => 'Version 1'],
    ]], 'File.versionInfo is missing the required field "id".'],
    'AI tag with no name' => [[[
        'type' => 'file', 'fileId' => 'f1', 'name' => 'a.jpg', 'filePath' => '/a.jpg', 'url' => 'https://ik.imagekit.io/test/a.jpg',
        'fileType' => 'image', 'createdAt' => '2026-08-03T00:00:00.000Z', 'updatedAt' => '2026-08-03T00:00:00.000Z',
        'AITags' => [['confidence' => 1]],
    ]], 'File.AITags is missing the required field "name".'],
]);

it('surfaces ImageKit errors and transport failures as the shared typed exceptions', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response(['message' => 'Invalid search query.'], 400)]);

    expect(fn () => ImageKitClient::files()->list(new ListRequest(searchQuery: 'name ~ x')))
        ->toThrow(function (RequestFailed $exception): void {
            expect($exception->status)->toBe(400)
                ->and($exception->imageKitMessage)->toBe('Invalid search query.');
        });

    Http::fake(['api.imagekit.io/*' => Http::failedConnection()]);

    expect(fn () => ImageKitClient::files()->list(new ListRequest))->toThrow(TransportError::class);
});
