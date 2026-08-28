<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Http;

use DateMalformedStringException;
use DateTimeImmutable;
use Thecyrilcril\ImageKitClient\Exceptions\UnexpectedResponse;

/**
 * Typed reads over one decoded JSON object from ImageKit. A field the docs
 * mark as always present is read strictly and throws UnexpectedResponse
 * when missing or of the wrong type; an optional field reads as null (or
 * empty) when absent, and a value of the wrong type also reads as null
 * rather than failing the whole response over a field nobody asked for.
 * Unknown fields are ignored.
 */
final readonly class Payload
{
    /**
     * A calendar date, a `T`, a time with optional fraction, and a zone
     * (`Z` or an offset).
     */
    private const string ISO_8601 = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})$/';

    /**
     * @param  array<string, mixed>  $data
     * @param  string  $asset  Names the object in error messages ("File", "Folder")
     */
    public function __construct(private array $data, private string $asset) {}

    public function string(string $key): string
    {
        if (! array_key_exists($key, $this->data)) {
            throw UnexpectedResponse::missingField($this->asset, $key);
        }

        if (! is_string($this->data[$key])) {
            throw UnexpectedResponse::malformedField($this->asset, $key, 'a string');
        }

        return $this->data[$key];
    }

    public function int(string $key): int
    {
        if (! array_key_exists($key, $this->data)) {
            throw UnexpectedResponse::missingField($this->asset, $key);
        }

        if (! is_int($this->data[$key])) {
            throw UnexpectedResponse::malformedField($this->asset, $key, 'an integer');
        }

        return $this->data[$key];
    }

    public function stringOrNull(string $key): ?string
    {
        $value = $this->data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public function intOrNull(string $key): ?int
    {
        $value = $this->data[$key] ?? null;

        return is_int($value) || is_float($value) ? (int) $value : null;
    }

    public function floatOrNull(string $key): ?float
    {
        $value = $this->data[$key] ?? null;

        return is_int($value) || is_float($value) ? (float) $value : null;
    }

    public function boolOrNull(string $key): ?bool
    {
        $value = $this->data[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    /**
     * ISO 8601, as ImageKit stamps `createdAt` and `updatedAt`
     * (`2026-08-01T10:00:00.123Z`). Only that shape is accepted: PHP would
     * happily read `""` as now and `"yesterday"` as a date, and neither is
     * a timestamp ImageKit sent.
     */
    public function dateTime(string $key): DateTimeImmutable
    {
        $value = $this->string($key);

        if (preg_match(self::ISO_8601, $value) !== 1) {
            throw UnexpectedResponse::malformedField($this->asset, $key, 'an ISO 8601 date-time');
        }

        try {
            return new DateTimeImmutable($value);
        } catch (DateMalformedStringException) {
            throw UnexpectedResponse::malformedField($this->asset, $key, 'an ISO 8601 date-time');
        }
    }

    /**
     * A list of strings; anything that is not a string is dropped.
     *
     * @return list<string>
     */
    public function strings(string $key): array
    {
        return array_values(array_filter($this->array($key), is_string(...)));
    }

    /**
     * A JSON object, as an associative array; empty when absent.
     *
     * @return array<string, mixed>
     */
    public function map(string $key): array
    {
        /** @var array<string, mixed> */
        return array_filter($this->array($key), is_string(...), ARRAY_FILTER_USE_KEY);
    }

    /**
     * A list of JSON objects, each as a Payload; anything else is dropped.
     *
     * @return list<self>
     */
    public function objects(string $key): array
    {
        $objects = [];

        foreach ($this->array($key) as $value) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $objects[] = new self($value, $this->asset.'.'.$key);
            }
        }

        return $objects;
    }

    /**
     * One nested JSON object, or null when absent.
     */
    public function objectOrNull(string $key): ?self
    {
        $value = $this->data[$key] ?? null;

        if (! is_array($value)) {
            return null;
        }

        /** @var array<string, mixed> $value */
        return new self($value, $this->asset.'.'.$key);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function array(string $key): array
    {
        $value = $this->data[$key] ?? null;

        return is_array($value) ? $value : [];
    }
}
