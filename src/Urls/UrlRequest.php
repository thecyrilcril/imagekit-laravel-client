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
    ) {
        if ($this->path === null && $this->src === null) {
            throw InvalidUrlRequest::missingSource();
        }

        if ($this->path !== null && $this->src !== null) {
            throw InvalidUrlRequest::ambiguousSource();
        }
    }
}
