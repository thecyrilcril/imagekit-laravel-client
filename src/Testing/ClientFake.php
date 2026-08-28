<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Testing;

use Closure;
use Override;
use PHPUnit\Framework\Assert;
use Thecyrilcril\ImageKitClient\Contracts\Client;
use Thecyrilcril\ImageKitClient\Contracts\Files;
use Thecyrilcril\ImageKitClient\Contracts\Urls;
use Thecyrilcril\ImageKitClient\Enums\AssetType;
use Thecyrilcril\ImageKitClient\Enums\UploadSourceKind;
use Thecyrilcril\ImageKitClient\Exceptions\RequestFailed;
use Thecyrilcril\ImageKitClient\Files\Concerns\PagesLazily;
use Thecyrilcril\ImageKitClient\Files\File;
use Thecyrilcril\ImageKitClient\Files\FileListing;
use Thecyrilcril\ImageKitClient\Files\Folder;
use Thecyrilcril\ImageKitClient\Files\ListRequest;
use Thecyrilcril\ImageKitClient\Files\UploadedFile;
use Thecyrilcril\ImageKitClient\Files\UploadRequest;
use Thecyrilcril\ImageKitClient\Urls\UrlRequest;

/**
 * The Client with no HTTP behind it, for a consumer's own tests. Install it
 * with `ImageKitClient::fake()`.
 *
 * Uploads, deletions and listings are recorded and answered with synthetic
 * typed results. URLs are pure string building, so `urls()` is the real
 * builder: a test sees the same URLs as production.
 *
 * Like Laravel's MailFake, this one object plays both the Client and its
 * files area, so `files()` returns itself.
 */
final class ClientFake implements Client, Files
{
    use PagesLazily;

    private const array IMAGE_EXTENSIONS = ['avif', 'bmp', 'gif', 'heic', 'heif', 'jpeg', 'jpg', 'png', 'svg', 'tif', 'tiff', 'webp'];

    /** @var list<UploadRequest> */
    private array $uploads = [];

    /** @var list<string> */
    private array $deletions = [];

    /** @var list<ListRequest> */
    private array $listings = [];

    /** @var list<File|Folder> */
    private array $seededItems = [];

    private bool $failUploads = false;

    public function __construct(private readonly Urls $urls) {}

    #[Override]
    public function files(): Files
    {
        return $this;
    }

    #[Override]
    public function urls(): Urls
    {
        return $this->urls;
    }

    /**
     * Records the request and answers as ImageKit would for a file stored
     * under the given name in the given folder: the id is `fake_<n>` for the
     * n-th upload, the name is kept as given (no unique suffix), the URLs
     * are built with the real builder, `size` is the byte count for a bytes
     * source and 0 for a data URI or a URL, and `fileType` follows the
     * extension.
     */
    #[Override]
    public function upload(UploadRequest $request): UploadedFile
    {
        $this->uploads[] = $request;

        if ($this->failUploads) {
            throw new RequestFailed(500, 'ImageKitClient::fake() was told to fail uploads.', null);
        }

        $filePath = self::filePathFor($request);

        return new UploadedFile(
            fileId: 'fake_'.count($this->uploads),
            name: $request->fileName,
            filePath: $filePath,
            url: $this->urls->build(new UrlRequest(path: $filePath)),
            fileType: self::fileTypeFor($request->fileName),
            size: $request->source->kind === UploadSourceKind::Bytes ? strlen($request->source->value) : 0,
            thumbnailUrl: $this->urls->build(new UrlRequest(path: $filePath, transformation: ['named' => 'ik_ml_thumbnail'])),
            tags: $request->tags ?? [],
            customCoordinates: $request->customCoordinates,
            customMetadata: $request->customMetadata ?? [],
            description: $request->description,
            isPrivateFile: $request->isPrivateFile,
            isPublished: $request->isPublished,
        );
    }

    #[Override]
    public function delete(string $fileId): void
    {
        $this->deletions[] = $fileId;
    }

    #[Override]
    public function list(ListRequest $request): FileListing
    {
        $this->listings[] = $request;

        $items = array_values(array_filter(
            $this->seededItems,
            static fn (File|Folder $item): bool => self::matchesType($item, $request->type) && self::matchesPath($item, $request->path),
        ));

        return new FileListing(array_slice($items, $request->skip ?? 0, $request->limit));
    }

    /**
     * Make every upload throw RequestFailed, as ImageKit rejecting it would,
     * so a consumer can test its own failure handling. The attempt is still
     * recorded.
     */
    public function failUploads(bool $fail = true): self
    {
        $this->failUploads = $fail;

        return $this;
    }

    /**
     * The items every listing is answered from, in this order. A listing
     * keeps the ones ImageKit would return for its `path` (that one folder
     * level) and `type` (files by default, as ImageKit's), then pages them
     * by `skip` and `limit`; the other filters are ignored, so assert on
     * the recorded ListRequest instead.
     */
    public function seedListing(File|Folder ...$items): self
    {
        $this->seededItems = array_values($items);

        return $this;
    }

    /**
     * @param  string|Closure(UploadRequest): bool  $fileNameOrCallback  a file name, or a callback given each recorded request
     */
    public function assertUploaded(string|Closure $fileNameOrCallback): void
    {
        Assert::assertNotEmpty(
            $this->matchingUploads($fileNameOrCallback),
            is_string($fileNameOrCallback)
                ? sprintf('Expected a file named [%s] to have been uploaded to ImageKit.', $fileNameOrCallback)
                : 'Expected an upload matching the callback to have been made to ImageKit.',
        );
    }

    /**
     * @param  string|Closure(UploadRequest): bool  $fileNameOrCallback  a file name, or a callback given each recorded request
     */
    public function assertNotUploaded(string|Closure $fileNameOrCallback): void
    {
        Assert::assertEmpty(
            $this->matchingUploads($fileNameOrCallback),
            is_string($fileNameOrCallback)
                ? sprintf('Expected no file named [%s] to have been uploaded to ImageKit.', $fileNameOrCallback)
                : 'Expected no upload matching the callback to have been made to ImageKit.',
        );
    }

    public function assertNothingUploaded(): void
    {
        $count = count($this->uploads);

        Assert::assertSame(0, $count, sprintf(
            'Expected no uploads to ImageKit, but %d %s made.',
            $count,
            $count === 1 ? 'was' : 'were',
        ));
    }

    public function assertDeleted(string $fileId): void
    {
        Assert::assertContains(
            $fileId,
            $this->deletions,
            sprintf('Expected ImageKit file [%s] to have been deleted.', $fileId),
        );
    }

    /**
     * @param  string|Closure(ListRequest): bool  $pathOrCallback  the listed path, or a callback given each recorded request
     */
    public function assertListed(string|Closure $pathOrCallback): void
    {
        Assert::assertNotEmpty(
            $this->matchingListings($pathOrCallback),
            is_string($pathOrCallback)
                ? sprintf('Expected a listing of path [%s] to have been requested from ImageKit.', $pathOrCallback)
                : 'Expected a listing matching the callback to have been requested from ImageKit.',
        );
    }

    /**
     * @param  string|Closure(UploadRequest): bool  $fileNameOrCallback
     * @return list<UploadRequest>
     */
    private function matchingUploads(string|Closure $fileNameOrCallback): array
    {
        $matches = is_string($fileNameOrCallback)
            ? static fn (UploadRequest $request): bool => $request->fileName === $fileNameOrCallback
            : $fileNameOrCallback;

        return array_values(array_filter($this->uploads, $matches));
    }

    /**
     * @param  string|Closure(ListRequest): bool  $pathOrCallback
     * @return list<ListRequest>
     */
    private function matchingListings(string|Closure $pathOrCallback): array
    {
        $matches = is_string($pathOrCallback)
            ? static fn (ListRequest $request): bool => $request->path === $pathOrCallback
            : $pathOrCallback;

        return array_values(array_filter($this->listings, $matches));
    }

    private static function filePathFor(UploadRequest $request): string
    {
        return '/'.ltrim(trim($request->folder ?? '', '/').'/'.$request->fileName, '/');
    }

    private static function fileTypeFor(string $fileName): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return in_array($extension, self::IMAGE_EXTENSIONS, true) ? 'image' : 'non-image';
    }

    /**
     * ImageKit's default for `type` is `file`; `All` is files and folders,
     * never file versions.
     */
    private static function matchesType(File|Folder $item, ?AssetType $type): bool
    {
        $wanted = $type ?? AssetType::File;

        return $wanted === AssetType::All
            ? $item->type !== AssetType::FileVersion
            : $item->type === $wanted;
    }

    /**
     * `path` names one folder level: the item's own folder must be that
     * path. `/avatars`, `avatars/` and `/avatars/` all mean the same folder,
     * and `/` (or `''`) the root.
     */
    private static function matchesPath(File|Folder $item, ?string $path): bool
    {
        if ($path === null) {
            return true;
        }

        $parent = dirname($item instanceof File ? $item->filePath : $item->folderPath);

        return $parent === '/'.trim($path, '/');
    }
}
