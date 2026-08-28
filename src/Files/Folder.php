<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use DateTimeImmutable;
use Thecyrilcril\ImageKitClient\Enums\AssetType;
use Thecyrilcril\ImageKitClient\Http\Payload;

/**
 * A folder in a listing made with `type: All` or `type: Folder`. To walk
 * into it, list again with `path: $folder->folderPath`.
 *
 * `type` is not a constructor argument on purpose: a Folder is always
 * `AssetType::Folder`, and the property exists so File and Folder can be
 * told apart the same way (`->type`) as well as by class.
 */
final readonly class Folder
{
    public AssetType $type;

    /**
     * @param  array<string, mixed>  $customMetadata
     */
    public function __construct(
        public string $folderId,
        public string $name,
        public string $folderPath,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $customMetadata = [],
    ) {
        $this->type = AssetType::Folder;
    }

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            folderId: $payload->string('folderId'),
            name: $payload->string('name'),
            folderPath: $payload->string('folderPath'),
            createdAt: $payload->dateTime('createdAt'),
            updatedAt: $payload->dateTime('updatedAt'),
            customMetadata: $payload->map('customMetadata'),
        );
    }
}
