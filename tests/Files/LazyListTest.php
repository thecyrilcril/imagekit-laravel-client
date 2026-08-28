<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Thecyrilcril\ImageKitClient\Enums\AssetType;
use Thecyrilcril\ImageKitClient\Exceptions\RequestFailed;
use Thecyrilcril\ImageKitClient\Facades\ImageKitClient;
use Thecyrilcril\ImageKitClient\Files\File;
use Thecyrilcril\ImageKitClient\Files\Folder;
use Thecyrilcril\ImageKitClient\Files\ListRequest;

/**
 * @return array<string, mixed>
 */
function fileItem(string $id): array
{
    return [
        'type' => 'file',
        'fileId' => $id,
        'name' => $id.'.jpg',
        'filePath' => '/kitwire/'.$id.'.jpg',
        'url' => 'https://ik.imagekit.io/test/kitwire/'.$id.'.jpg',
        'fileType' => 'image',
        'createdAt' => '2026-08-03T00:00:00.000Z',
        'updatedAt' => '2026-08-03T00:00:00.000Z',
    ];
}

/**
 * @return array<string, mixed>
 */
function folderItem(string $path): array
{
    return [
        'type' => 'folder',
        'folderId' => 'd_'.md5($path),
        'name' => basename($path),
        'folderPath' => $path,
        'createdAt' => '2026-07-01T00:00:00.000Z',
        'updatedAt' => '2026-07-15T00:00:00.000Z',
    ];
}

it('pages by the request limit from its skip and stops on the first short page', function (): void {
    Http::fake([
        'api.imagekit.io/v1/files?limit=2&skip=10&path=%2Fkitwire' => Http::response([fileItem('a'), fileItem('b')], 200),
        'api.imagekit.io/v1/files?limit=2&skip=12&path=%2Fkitwire' => Http::response([fileItem('c'), fileItem('d')], 200),
        'api.imagekit.io/v1/files?limit=2&skip=14&path=%2Fkitwire' => Http::response([fileItem('e')], 200),
    ]);

    $items = ImageKitClient::files()->lazy(new ListRequest(limit: 2, skip: 10, path: '/kitwire'))->all();

    expect(array_map(fn (File|Folder $item): string => $item instanceof File ? $item->fileId : $item->folderPath, $items))
        ->toBe(['a', 'b', 'c', 'd', 'e']);
    Http::assertSentCount(3);
    Http::assertSentInOrder([
        fn (Request $request): bool => $request->url() === 'https://api.imagekit.io/v1/files?limit=2&skip=10&path=%2Fkitwire',
        fn (Request $request): bool => $request->url() === 'https://api.imagekit.io/v1/files?limit=2&skip=12&path=%2Fkitwire',
        fn (Request $request): bool => $request->url() === 'https://api.imagekit.io/v1/files?limit=2&skip=14&path=%2Fkitwire',
    ]);
});

it('stops after a full page that is followed by an empty one', function (): void {
    Http::fake([
        'api.imagekit.io/v1/files?limit=2&skip=0' => Http::response([fileItem('a'), fileItem('b')], 200),
        'api.imagekit.io/v1/files?limit=2&skip=2' => Http::response([], 200),
    ]);

    $items = ImageKitClient::files()->lazy(new ListRequest(limit: 2))->all();

    expect($items)->toHaveCount(2);
    Http::assertSentCount(2);
});

it('uses the default page size and skip 0 when the request names neither, keeping the other filters', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response([fileItem('a')], 200)]);

    $items = ImageKitClient::files()->lazy(new ListRequest(type: AssetType::All, searchQuery: 'createdAt > "7d"'))->all();

    expect($items)->toHaveCount(1)
        ->and(ListRequest::DEFAULT_PAGE_SIZE)->toBe(100);
    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->url()
        === 'https://api.imagekit.io/v1/files?limit=100&skip=0&type=all&searchQuery=createdAt%20%3E%20%227d%22');
});

it('yields nothing and sends one request when nothing matches', function (): void {
    Http::fake(['api.imagekit.io/*' => Http::response([], 200)]);

    expect(ImageKitClient::files()->lazy(new ListRequest(limit: 50))->all())->toBe([]);
    Http::assertSentCount(1);
});

it('is lazy: nothing is sent until the collection is consumed, and a page is fetched only when reached', function (): void {
    Http::fake([
        'api.imagekit.io/v1/files?limit=1&skip=0' => Http::response([fileItem('a')], 200),
        'api.imagekit.io/v1/files?limit=1&skip=1' => Http::response([fileItem('b')], 200),
        'api.imagekit.io/v1/files?limit=1&skip=2' => Http::response([], 200),
    ]);

    $lazy = ImageKitClient::files()->lazy(new ListRequest(limit: 1));
    Http::assertNothingSent();

    $first = $lazy->first();

    expect($first)->toBeInstanceOf(File::class)
        ->and($first->fileId)->toBe('a');
    Http::assertSentCount(1);
});

it('mixes Files and Folders across pages so a caller can walk sub-folders', function (): void {
    Http::fake([
        'api.imagekit.io/v1/files?limit=2&skip=0&path=%2Fkitwire&type=all' => Http::response([folderItem('/kitwire/avatars'), fileItem('a')], 200),
        'api.imagekit.io/v1/files?limit=2&skip=2&path=%2Fkitwire&type=all' => Http::response([folderItem('/kitwire/docs')], 200),
    ]);

    $items = ImageKitClient::files()->lazy(new ListRequest(limit: 2, path: '/kitwire', type: AssetType::All));

    $folders = $items->filter(fn (File|Folder $item): bool => $item instanceof Folder)
        ->map(fn (File|Folder $item): string => $item instanceof Folder ? $item->folderPath : '')
        ->values()
        ->all();

    expect($folders)->toBe(['/kitwire/avatars', '/kitwire/docs']);
});

it('lets a page failure surface from the consumer loop', function (): void {
    Http::fake([
        'api.imagekit.io/v1/files?limit=1&skip=0' => Http::response([fileItem('a')], 200),
        'api.imagekit.io/v1/files?limit=1&skip=1' => Http::response(['message' => 'Too much.'], 400),
    ]);

    expect(fn () => ImageKitClient::files()->lazy(new ListRequest(limit: 1))->all())
        ->toThrow(RequestFailed::class, 'ImageKit responded with HTTP 400: Too much.');
});
