<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Files;

use Thecyrilcril\ImageKitClient\Enums\ExtensionState;
use Thecyrilcril\ImageKitClient\Http\Payload;

/**
 * The state of each extension an upload asked for, keyed by extension
 * under its property name (the wire keys are `ai-auto-description`,
 * `ai-tasks`, `aws-auto-tagging`, `google-auto-tagging`, `remove-bg`).
 * An extension the upload did not ask for, or a state this package has not
 * seen, reads as null.
 */
final readonly class ExtensionStatus
{
    public function __construct(
        public ?ExtensionState $aiAutoDescription = null,
        public ?ExtensionState $aiTasks = null,
        public ?ExtensionState $awsAutoTagging = null,
        public ?ExtensionState $googleAutoTagging = null,
        public ?ExtensionState $removeBg = null,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            aiAutoDescription: self::state($payload, 'ai-auto-description'),
            aiTasks: self::state($payload, 'ai-tasks'),
            awsAutoTagging: self::state($payload, 'aws-auto-tagging'),
            googleAutoTagging: self::state($payload, 'google-auto-tagging'),
            removeBg: self::state($payload, 'remove-bg'),
        );
    }

    private static function state(Payload $payload, string $key): ?ExtensionState
    {
        $value = $payload->stringOrNull($key);

        return $value === null ? null : ExtensionState::tryFrom($value);
    }
}
