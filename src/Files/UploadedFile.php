<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use Thecyrilcril\ImageKitClient\Exceptions\UnexpectedResponse;
use Thecyrilcril\ImageKitClient\Http\Payload;

/**
 * The file ImageKit stored, as its upload response describes it: every
 * documented field, typed. The fields ImageKit only sends when asked for
 * through `responseFields` (`tags`, `customCoordinates`, `isPrivateFile`,
 * `isPublished`, `customMetadata`, `embeddedMetadata`, `metadata`,
 * `selectedFieldsSchema`) read as null, or as an empty list or map, when
 * they were not asked for; so do the dimensions of a non-image and the
 * video group of an image. Unknown fields are ignored.
 *
 * `fileType` is `image` or `non-image` and stays a string, as on File: the
 * value set is ImageKit's to grow.
 */
final readonly class UploadedFile
{
    /**
     * @param  list<string>  $tags
     * @param  list<AITag>  $aiTags
     * @param  array<string, mixed>  $customMetadata
     * @param  array<string, mixed>  $embeddedMetadata
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $selectedFieldsSchema
     */
    public function __construct(
        public string $fileId,
        public string $name,
        public string $filePath,
        public string $url,
        public string $fileType,
        public int $size,
        public ?string $thumbnailUrl = null,
        public ?int $width = null,
        public ?int $height = null,
        public array $tags = [],
        public array $aiTags = [],
        public ?string $customCoordinates = null,
        public array $customMetadata = [],
        public ?string $description = null,
        public ?bool $isPrivateFile = null,
        public ?bool $isPublished = null,
        public array $embeddedMetadata = [],
        public array $metadata = [],
        public array $selectedFieldsSchema = [],
        public ?VersionInfo $versionInfo = null,
        public ?ExtensionStatus $extensionStatus = null,
        public ?int $duration = null,
        public ?int $bitRate = null,
        public ?string $audioCodec = null,
        public ?string $videoCodec = null,
    ) {}

    /**
     * @throws UnexpectedResponse when a field the docs mark as always present is missing or mistyped
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            fileId: $payload->string('fileId'),
            name: $payload->string('name'),
            filePath: $payload->string('filePath'),
            url: $payload->string('url'),
            fileType: $payload->string('fileType'),
            size: $payload->int('size'),
            thumbnailUrl: $payload->stringOrNull('thumbnailUrl'),
            width: $payload->intOrNull('width'),
            height: $payload->intOrNull('height'),
            tags: $payload->strings('tags'),
            aiTags: array_map(AITag::fromPayload(...), $payload->objects('AITags')),
            customCoordinates: $payload->stringOrNull('customCoordinates'),
            customMetadata: $payload->map('customMetadata'),
            description: $payload->stringOrNull('description'),
            isPrivateFile: $payload->boolOrNull('isPrivateFile'),
            isPublished: $payload->boolOrNull('isPublished'),
            embeddedMetadata: $payload->map('embeddedMetadata'),
            metadata: $payload->map('metadata'),
            selectedFieldsSchema: $payload->map('selectedFieldsSchema'),
            versionInfo: ($version = $payload->objectOrNull('versionInfo')) === null ? null : VersionInfo::fromPayload($version),
            extensionStatus: ($status = $payload->objectOrNull('extensionStatus')) === null ? null : ExtensionStatus::fromPayload($status),
            duration: $payload->intOrNull('duration'),
            bitRate: $payload->intOrNull('bitRate'),
            audioCodec: $payload->stringOrNull('audioCodec'),
            videoCodec: $payload->stringOrNull('videoCodec'),
        );
    }
}
