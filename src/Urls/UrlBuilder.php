<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Urls;

use Override;
use Thecyrilcril\ImageKitClient\Configuration;
use Thecyrilcril\ImageKitClient\Contracts\Urls;
use Thecyrilcril\ImageKitClient\Enums\TransformationPosition;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidTransformation;

/**
 * Renders a UrlRequest as a delivery URL. Steps in a chain are joined with
 * `:`, parameters within a step with `,`, and a key with its value by `-`.
 */
final readonly class UrlBuilder implements Urls
{
    private const string TRANSFORMATION_PARAMETER = 'tr';

    public function __construct(private Configuration $configuration) {}

    #[Override]
    public function build(UrlRequest $request): string
    {
        $transformation = $this->renderTransformation($request->transformation);

        if ($request->src !== null) {
            return $this->withQuery($request->src, $transformation, $request->queryParameters);
        }

        $endpoint = rtrim($this->configuration->urlEndpoint, '/');
        $path = ltrim((string) $request->path, '/');
        $position = $request->position ?? $this->configuration->transformationPosition;

        if ($position === TransformationPosition::Query) {
            return $this->withQuery($endpoint.'/'.$path, $transformation, $request->queryParameters);
        }

        $prefix = $transformation === '' ? '' : self::TRANSFORMATION_PARAMETER.':'.$transformation.'/';

        return $this->withQuery($endpoint.'/'.$prefix.$path, '', $request->queryParameters);
    }

    /**
     * @param  array<string, string|int|float|bool>  $queryParameters
     */
    private function withQuery(string $url, string $transformation, array $queryParameters): string
    {
        $parts = [];

        if ($transformation !== '') {
            $parts[] = self::TRANSFORMATION_PARAMETER.'='.$transformation;
        }

        if ($queryParameters !== []) {
            $parts[] = http_build_query(
                array_map(static fn (string|int|float|bool $value): string|int|float => is_bool($value) ? ($value ? 'true' : 'false') : $value, $queryParameters),
                '',
                '&',
                PHP_QUERY_RFC3986,
            );
        }

        if ($parts === []) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').implode('&', $parts);
    }

    /**
     * A list of arrays is a chain; anything else is a single step.
     *
     * @param  array<string, mixed>|list<array<string, mixed>>  $transformation
     *
     * @throws InvalidTransformation
     */
    private function renderTransformation(array $transformation): string
    {
        $steps = array_is_list($transformation) ? $transformation : [$transformation];

        $rendered = [];

        foreach ($steps as $step) {
            $parameters = [];

            foreach ($step as $key => $value) {
                $parameters[] = $this->renderParameter((string) $key, $value);
            }

            if ($parameters !== []) {
                $rendered[] = implode(',', $parameters);
            }
        }

        return $this->encode(implode(':', $rendered));
    }

    /**
     * @throws InvalidTransformation
     */
    private function renderParameter(string $key, mixed $value): string
    {
        if (! is_scalar($value)) {
            throw InvalidTransformation::unrenderableValue($key, $value);
        }

        if ($key === TransformationCodes::RAW) {
            return (string) $value;
        }

        $code = TransformationCodes::resolve($key);

        // A bare code: `true` on an effect, or the SDK's `'-'` convention.
        if (($value === true && TransformationCodes::isBareWhenTrue($code)) || $value === '-') {
            return $code;
        }

        if (is_bool($value)) {
            return $code.'-'.($value ? 'true' : 'false');
        }

        // ImageKit spells `/` as `@@` inside a value (default images, layer
        // paths) so the value cannot be mistaken for a path segment.
        return $code.'-'.str_replace('/', '@@', trim((string) $value, '/'));
    }

    /**
     * Percent-encodes the rendered Transformation, keeping the three
     * characters ImageKit's own syntax relies on readable.
     */
    private function encode(string $transformation): string
    {
        return strtr(rawurlencode($transformation), ['%2C' => ',', '%3A' => ':', '%40' => '@']);
    }
}
