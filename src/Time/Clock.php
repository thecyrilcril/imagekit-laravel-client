<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Time;

use DateTimeImmutable;

/**
 * Where the UrlBuilder reads "now" from when a signed URL has an expiry.
 * Bound to SystemClock by the service provider; tests bind a fixed one so
 * an expiring signature is the same on every run.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
