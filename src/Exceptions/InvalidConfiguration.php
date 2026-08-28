<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Exceptions;

/**
 * Thrown when the Client is first resolved from the container, so a bad
 * .env surfaces at boot rather than at the first upload.
 */
final class InvalidConfiguration extends ImageKitClientException
{
    public static function missing(string $key, string $environmentVariable): self
    {
        return new self(sprintf(
            'ImageKit Client is not configured: [imagekit-client.%s] is missing. Set %s in your .env file.',
            $key,
            $environmentVariable,
        ));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function notOneOf(string $key, array $allowed, mixed $value): self
    {
        return self::invalid($key, sprintf('must be one of "%s"', implode('", "', $allowed)), $value);
    }

    public static function notAWholeNumber(string $key, mixed $value): self
    {
        return self::invalid($key, 'must be a whole number of at least 0', $value);
    }

    private static function invalid(string $key, string $rule, mixed $value): self
    {
        return new self(sprintf(
            'Invalid ImageKit Client configuration: [imagekit-client.%s] %s, got %s.',
            $key,
            $rule,
            self::describe($value),
        ));
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
