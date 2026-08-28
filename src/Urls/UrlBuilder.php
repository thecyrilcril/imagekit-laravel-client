<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Urls;

use Override;
use Thecyrilcril\ImageKitClient\Configuration;
use Thecyrilcril\ImageKitClient\Contracts\Urls;
use Thecyrilcril\ImageKitClient\Enums\TransformationPosition;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidTransformation;
use Thecyrilcril\ImageKitClient\Time\Clock;

/**
 * Renders a UrlRequest as a delivery URL. Steps in a chain are joined with
 * `:`, parameters within a step with `,`, and a key with its value by `-`.
 *
 * A signed URL carries `ik-s`: the lowercase hex HMAC-SHA1, keyed with the
 * private key, of everything after the trailing-slashed endpoint followed by
 * the expiry timestamp (`9999999999` when the URL never expires). An expiring
 * URL also carries that timestamp as `ik-t`, before `ik-s`, as the docs and
 * ImageKit's own SDKs order them.
 */
final readonly class UrlBuilder implements Urls
{
    private const string TRANSFORMATION_PARAMETER = 'tr';

    private const string SIGNATURE_PARAMETER = 'ik-s';

    private const string EXPIRY_PARAMETER = 'ik-t';

    private const string NEVER_EXPIRES = '9999999999';

    public function __construct(
        private Configuration $configuration,
        private Clock $clock,
    ) {}

    #[Override]
    public function build(UrlRequest $request): string
    {
        $transformation = $this->renderTransformation($request->transformation);

        if ($request->src !== null) {
            return $this->withQuery($request->src, $transformation, $request->queryParameters);
        }

        $path = ltrim((string) $request->path, '/');
        $position = $request->position ?? $this->configuration->transformationPosition;

        if ($request->signed) {
            $path = self::percentEncodePath($path);
        }

        // Everything after the endpoint: what a signature is made of.
        $relative = $position === TransformationPosition::Query
            ? $this->withQuery($path, $transformation, $request->queryParameters)
            : $this->withQuery(self::transformationPrefix($transformation).$path, '', $request->queryParameters);

        if ($request->signed) {
            $relative = $this->sign($relative, $request->expiresIn);
        }

        return rtrim($this->configuration->urlEndpoint, '/').'/'.$relative;
    }

    private static function transformationPrefix(string $transformation): string
    {
        return $transformation === '' ? '' : self::TRANSFORMATION_PARAMETER.':'.$transformation.'/';
    }

    /**
     * The CDN verifies the signature against the request it receives, and
     * browsers percent-encode a URL before sending it, so a signed path must
     * already be in that form. Encodes what a browser would (non-ASCII,
     * whitespace, the quote, hash, angle brackets, question mark, backtick
     * and braces) and leaves `%` alone, so a path that is already encoded is
     * not encoded twice.
     */
    private static function percentEncodePath(string $path): string
    {
        return preg_replace_callback(
            '/[^!$-;=@-_a-z|~]/',
            static fn (array $match): string => rawurlencode($match[0]),
            $path,
        ) ?? $path;
    }

    /**
     * @param  string  $relative  the URL without its endpoint, as the CDN will receive it
     */
    private function sign(string $relative, ?int $expiresIn): string
    {
        $expiry = $expiresIn === null
            ? self::NEVER_EXPIRES
            : (string) ($this->clock->now()->getTimestamp() + $expiresIn);

        $signature = hash_hmac('sha1', $relative.$expiry, $this->configuration->privateKey);

        $parts = $expiresIn === null ? [] : [self::EXPIRY_PARAMETER.'='.$expiry];
        $parts[] = self::SIGNATURE_PARAMETER.'='.$signature;

        return self::appendQuery($relative, $parts);
    }

    /**
     * @param  list<string>  $parts  already-encoded `key=value` pairs
     */
    private static function appendQuery(string $url, array $parts): string
    {
        if ($parts === []) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').implode('&', $parts);
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
                array_map(static fn (string|int|float|bool $value): string|int|float => is_bool($value) ? self::spell($value) : $value, $queryParameters),
                '',
                '&',
                PHP_QUERY_RFC3986,
            );
        }

        return self::appendQuery($url, $parts);
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

        return implode(':', $rendered);
    }

    /**
     * @throws InvalidTransformation
     */
    private function renderParameter(string $key, mixed $value): string
    {
        if (! is_scalar($value)) {
            throw InvalidTransformation::unrenderableValue($key, $value);
        }

        // Verbatim: the caller owns the syntax and the encoding. In a signed
        // URL that includes percent-encoding anything a browser would encode
        // on the way out, or the CDN will verify a different string.
        if ($key === TransformationCodes::RAW) {
            return (string) $value;
        }

        $code = TransformationCodes::resolve($key);

        // A bare code: `true` on an effect, or the SDK's `'-'` / `''` convention.
        if (($value === true && TransformationCodes::isBareWhenTrue($code)) || $value === '-' || $value === '') {
            return $code;
        }

        if (is_bool($value)) {
            return $code.'-'.self::spell($value);
        }

        return $code.'-'.$this->encode(trim((string) $value, '/'));
    }

    private static function spell(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    /**
     * ImageKit spells `/` as `@@` inside a value (default images, layer
     * paths) so the value cannot be mistaken for a path segment. Everything
     * else that is not URL safe is percent-encoded, as the docs show for
     * prompts (`prompt-snow%20road`).
     */
    private function encode(string $value): string
    {
        return strtr(rawurlencode(str_replace('/', '@@', $value)), ['%40' => '@']);
    }
}
