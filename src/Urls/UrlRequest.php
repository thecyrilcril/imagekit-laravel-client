<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Urls;

use Thecyrilcril\ImageKitClient\Enums\TransformationPosition;
use Thecyrilcril\ImageKitClient\Exceptions\InvalidUrlRequest;

/**
 * What to build a delivery URL from. Give exactly one of `path` (relative to
 * the configured endpoint) or `src` (an absolute URL that already lives on
 * an ImageKit endpoint).
 *
 * `transformation` is a flat Transformation (`['width' => 200]`) or a chain:
 * a list of flat Transformations run in order.
 *
 * `position` overrides the configured default; it is ignored with `src`,
 * which always carries its Transformation in the query string.
 *
 * `signed` appends an `ik-s` signature made with the private key. Signing
 * needs a `path`: the endpoint has to be stripped from the string that is
 * signed, and a `src` may live on any endpoint. `expiresIn` (seconds from
 * now) adds `ik-t`, after which the CDN answers 401; without it the
 * signature never expires.
 */
final readonly class UrlRequest
{
    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $transformation
     * @param  array<string, string|int|float|bool>  $queryParameters
     *
     * @throws InvalidUrlRequest
     */
    public function __construct(
        public ?string $path = null,
        public ?string $src = null,
        public array $transformation = [],
        public ?TransformationPosition $position = null,
        public array $queryParameters = [],
        public bool $signed = false,
        public ?int $expiresIn = null,
    ) {
        if ($this->path === null && $this->src === null) {
            throw InvalidUrlRequest::missingSource();
        }

        if ($this->path !== null && $this->src !== null) {
            throw InvalidUrlRequest::ambiguousSource();
        }

        if ($this->signed && $this->src !== null) {
            throw InvalidUrlRequest::cannotSignSrc();
        }

        if ($this->expiresIn !== null && ! $this->signed) {
            throw InvalidUrlRequest::expiryWithoutSigning();
        }

        if ($this->expiresIn !== null && $this->expiresIn <= 0) {
            throw InvalidUrlRequest::expiryNotPositive($this->expiresIn);
        }
    }
}
