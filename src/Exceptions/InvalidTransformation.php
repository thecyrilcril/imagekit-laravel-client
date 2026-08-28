<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

/**
 * Thrown while rendering a Transformation, so a typo in a Preset fails loudly
 * instead of emitting a URL ImageKit cannot serve.
 */
final class InvalidTransformation extends ImageKitClientException
{
    public static function unknownKey(string $key): self
    {
        return new self(sprintf(
            'Unknown transformation key [%s]. Use a documented alias, an ImageKit short code, or [raw].',
            $key,
        ));
    }

    public static function unrenderableValue(string $key, mixed $value): self
    {
        return new self(sprintf(
            'Transformation [%s] must be a string, number or boolean, got %s.',
            $key,
            get_debug_type($value),
        ));
    }
}
