<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Enums;

/**
 * Where a built URL carries its Transformation. Path is the default
 * (ADR-0002 in thecyrilcril/laravel-imagekit): the CDN caches by URL text, so
 * the position must not drift between releases.
 */
enum TransformationPosition: string
{
    case Path = 'path';
    case Query = 'query';
}
