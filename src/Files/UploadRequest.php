<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use Thecyrilcril\ImageKitClient\Enums\ResponseField;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidUploadRequest;

/**
 * What to upload and how ImageKit should store it. Every documented field
 * of the upload API is here under its API name; a null field stays off the
 * wire, so ImageKit applies its own default (for example, unique file names
 * are on unless `useUniqueFileName` is false).
 *
 * The shapes of `customMetadata`, `extensions` and `transformation` are
 * ImageKit's own and are sent as JSON verbatim: see the upload API
 * reference for the keys each accepts.
 */
final readonly class UploadRequest
{
    /**
     * @param  UploadSource  $source  The file content: bytes, a data URI, or a public URL
     * @param  string  $fileName  The name to store the file under, with extension
     * @param  bool|null  $useUniqueFileName  Append a random suffix so the name never collides (ImageKit's default is true)
     * @param  string|null  $folder  Destination folder path, created when missing
     * @param  list<string>|null  $tags  Tags to file the upload under, searchable in listings
     * @param  bool|null  $isPrivateFile  Serve only through signed URLs
     * @param  bool|null  $isPublished  False stores a draft reachable only from the media library
     * @param  string|null  $customCoordinates  Focus area as `x,y,width,height`
     * @param  array<string, mixed>|null  $customMetadata  Values for custom metadata fields already defined in the account
     * @param  list<ResponseField>|null  $responseFields  Extra fields to include in the response
     * @param  list<array<string, mixed>>|null  $extensions  Extensions to run after the upload, each `['name' => ..., ...options]`
     * @param  string|null  $webhookUrl  Called when the extensions finish
     * @param  bool|null  $overwriteFile  Replace an existing file with the same path (when `useUniqueFileName` is false)
     * @param  bool|null  $overwriteAITags  Drop the existing file's AI tags before applying the new ones
     * @param  bool|null  $overwriteTags  Drop the existing file's tags before applying the new ones
     * @param  bool|null  $overwriteCustomMetadata  Drop the existing file's custom metadata before applying the new one
     * @param  array<string, mixed>|null  $transformation  `['pre' => ..., 'post' => [...]]` transformations applied at upload
     * @param  string|null  $checks  A server-side guard expression, such as `'file.size' < '1MB'`
     * @param  string|null  $description  Free text stored with the file
     *
     * @throws InvalidUploadRequest when `fileName` is empty
     */
    public function __construct(
        public UploadSource $source,
        public string $fileName,
        public ?bool $useUniqueFileName = null,
        public ?string $folder = null,
        public ?array $tags = null,
        public ?bool $isPrivateFile = null,
        public ?bool $isPublished = null,
        public ?string $customCoordinates = null,
        public ?array $customMetadata = null,
        public ?array $responseFields = null,
        public ?array $extensions = null,
        public ?string $webhookUrl = null,
        public ?bool $overwriteFile = null,
        public ?bool $overwriteAITags = null,
        public ?bool $overwriteTags = null,
        public ?bool $overwriteCustomMetadata = null,
        public ?array $transformation = null,
        public ?string $checks = null,
        public ?string $description = null,
    ) {
        if ($this->fileName === '') {
            throw InvalidUploadRequest::emptyFileName();
        }
    }
}
