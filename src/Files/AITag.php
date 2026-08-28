<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use Thecyrilcril\ImageKitClient\Http\Payload;

/**
 * One tag an AI extension put on a file: the label, how sure the model was
 * (0–100), and which extension produced it (`google-auto-tagging`,
 * `aws-auto-tagging`). Only the label is always present.
 */
final readonly class AITag
{
    public function __construct(
        public string $name,
        public ?float $confidence = null,
        public ?string $source = null,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            name: $payload->string('name'),
            confidence: $payload->floatOrNull('confidence'),
            source: $payload->stringOrNull('source'),
        );
    }
}
