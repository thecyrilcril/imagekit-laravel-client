<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use Thecyrilcril\ImageKitClient\Http\Payload;

/**
 * Which version of a file a listing entry describes.
 */
final readonly class VersionInfo
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            id: $payload->string('id'),
            name: $payload->string('name'),
        );
    }
}
