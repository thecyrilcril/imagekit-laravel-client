<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient;

use Illuminate\Support\Arr;
use Thecyrilcril\ImageKitClient\Enums\TransformationPosition;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidConfiguration;

/**
 * The validated shape of config/imagekit-client.php. Built once, at first
 * resolve of the Client, so every consumer downstream can trust the values.
 *
 * Optional keys fall back to their defaults when null: mergeConfigFrom only
 * merges the first level, so a published copy that trims the `http` array
 * would otherwise hand us nulls.
 */
final readonly class Configuration
{
    private const array ENVIRONMENT_VARIABLES = [
        'public_key' => 'IMAGEKIT_PUBLIC_KEY',
        'private_key' => 'IMAGEKIT_PRIVATE_KEY',
        'url_endpoint' => 'IMAGEKIT_URL_ENDPOINT',
    ];

    public function __construct(
        public string $publicKey,
        public string $privateKey,
        public string $urlEndpoint,
        public TransformationPosition $transformationPosition = TransformationPosition::Path,
        public int $timeout = 30,
        public int $retries = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidConfiguration
     */
    public static function fromArray(array $config): self
    {
        return new self(
            publicKey: self::credential($config, 'public_key'),
            privateKey: self::credential($config, 'private_key'),
            urlEndpoint: self::credential($config, 'url_endpoint'),
            transformationPosition: self::transformationPosition($config),
            timeout: self::wholeNumber($config, 'http.timeout', 30),
            retries: self::wholeNumber($config, 'http.retries', 0),
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function credential(array $config, string $key): string
    {
        $value = Arr::get($config, $key);

        if (! is_string($value) || $value === '') {
            throw InvalidConfiguration::missing($key, self::ENVIRONMENT_VARIABLES[$key]);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function transformationPosition(array $config): TransformationPosition
    {
        $value = Arr::get($config, 'transformation_position');

        if ($value === null) {
            return TransformationPosition::Path;
        }

        $position = is_string($value) ? TransformationPosition::tryFrom($value) : null;

        if ($position === null) {
            throw InvalidConfiguration::notOneOf(
                'transformation_position',
                array_column(TransformationPosition::cases(), 'value'),
                $value,
            );
        }

        return $position;
    }

    /**
     * Env values arrive as strings ("45"), so digits-only strings are
     * accepted; anything else that is not a non-negative int is rejected
     * rather than silently cast to 0.
     *
     * @param  array<string, mixed>  $config
     */
    private static function wholeNumber(array $config, string $key, int $default): int
    {
        $value = Arr::get($config, $key);

        if ($value === null) {
            return $default;
        }

        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        if (is_int($value) && $value >= 0) {
            return $value;
        }

        throw InvalidConfiguration::notAWholeNumber($key, $value);
    }
}
