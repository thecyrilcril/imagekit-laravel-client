<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

/**
 * ImageKit answered 2xx, but the body is not the shape the docs promise:
 * not JSON, a listing that is not an array, an asset with no `type`, or a
 * required field missing or of the wrong type. Distinct from ImageKitError
 * (ImageKit said no) and TransportError (ImageKit was unreachable): this is
 * "ImageKit said yes, and we could not read the answer".
 */
final class UnexpectedResponse extends ImageKitClientException
{
    public static function notAList(): self
    {
        return new self('ImageKit answered with a body that is not a JSON array of assets.');
    }

    public static function itemNotAnObject(int $index): self
    {
        return new self(sprintf('Asset #%d in the listing is not a JSON object.', $index));
    }

    public static function unknownAssetType(mixed $type): self
    {
        return new self(sprintf('Asset has an unknown type %s; expected file, file-version or folder.', self::describe($type)));
    }

    public static function missingField(string $asset, string $field): self
    {
        return new self(sprintf('%s is missing the required field "%s".', $asset, $field));
    }

    public static function malformedField(string $asset, string $field, string $expected): self
    {
        return new self(sprintf('%s field "%s" is not %s.', $asset, $field, $expected));
    }

    private static function describe(mixed $value): string
    {
        return match (true) {
            is_string($value) => sprintf('"%s"', $value),
            is_scalar($value) => var_export($value, true),
            default => get_debug_type($value),
        };
    }
}
