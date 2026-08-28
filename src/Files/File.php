<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use DateTimeImmutable;
use Thecyrilcril\ImageKitClient\Enums\AssetType;
use Thecyrilcril\ImageKitClient\Http\Payload;

/**
 * A file, or a file version, as ImageKit describes it in a listing: every
 * documented field, typed. `type` is File or FileVersion. Fields ImageKit
 * only sets for some files (dimensions, alpha, the video group) are null
 * when absent; the list and object fields are empty instead. Unknown fields
 * are ignored.
 *
 * `isPrivateFile` and `isPublished` are documented as always present; should
 * one be missing, the file reads as ImageKit's own defaults (public, published).
 *
 * `fileType` is `image` or `non-image`. It stays a string: the value set is
 * ImageKit's to grow, and a listing must not fail over a value this package
 * has not seen.
 */
final readonly class File
{
    /**
     * @param  AssetType::File|AssetType::FileVersion  $type
     * @param  list<string>  $tags
     * @param  list<AITag>  $aiTags
     * @param  array<string, mixed>  $customMetadata
     * @param  array<string, mixed>  $embeddedMetadata
     * @param  array<string, mixed>  $selectedFieldsSchema
     */
    public function __construct(
        public string $fileId,
        public AssetType $type,
        public string $name,
        public string $filePath,
        public string $url,
        public string $fileType,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?string $thumbnail = null,
        public ?string $mime = null,
        public ?int $size = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?bool $hasAlpha = null,
        public array $tags = [],
        public array $aiTags = [],
        public ?string $customCoordinates = null,
        public array $customMetadata = [],
        public ?string $description = null,
        public array $embeddedMetadata = [],
        public array $selectedFieldsSchema = [],
        public bool $isPrivateFile = false,
        public bool $isPublished = true,
        public ?VersionInfo $versionInfo = null,
        public ?int $duration = null,
        public ?int $bitRate = null,
        public ?string $audioCodec = null,
        public ?string $videoCodec = null,
    ) {}

    /**
     * @param  AssetType::File|AssetType::FileVersion  $type
     */
    public static function fromPayload(Payload $payload, AssetType $type): self
    {
        return new self(
            fileId: $payload->string('fileId'),
            type: $type,
            name: $payload->string('name'),
            filePath: $payload->string('filePath'),
            url: $payload->string('url'),
            fileType: $payload->string('fileType'),
            createdAt: $payload->dateTime('createdAt'),
            updatedAt: $payload->dateTime('updatedAt'),
            thumbnail: $payload->stringOrNull('thumbnail'),
            mime: $payload->stringOrNull('mime'),
            size: $payload->intOrNull('size'),
            width: $payload->intOrNull('width'),
            height: $payload->intOrNull('height'),
            hasAlpha: $payload->boolOrNull('hasAlpha'),
            tags: $payload->strings('tags'),
            aiTags: array_map(AITag::fromPayload(...), $payload->objects('AITags')),
            customCoordinates: $payload->stringOrNull('customCoordinates'),
            customMetadata: $payload->map('customMetadata'),
            description: $payload->stringOrNull('description'),
            embeddedMetadata: $payload->map('embeddedMetadata'),
            selectedFieldsSchema: $payload->map('selectedFieldsSchema'),
            isPrivateFile: $payload->boolOrNull('isPrivateFile') ?? false,
            isPublished: $payload->boolOrNull('isPublished') ?? true,
            versionInfo: ($version = $payload->objectOrNull('versionInfo')) === null ? null : VersionInfo::fromPayload($version),
            duration: $payload->intOrNull('duration'),
            bitRate: $payload->intOrNull('bitRate'),
            audioCodec: $payload->stringOrNull('audioCodec'),
            videoCodec: $payload->stringOrNull('videoCodec'),
        );
    }
}
