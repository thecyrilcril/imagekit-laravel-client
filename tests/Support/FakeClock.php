<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Tests\Support;

use DateTimeImmutable;
use Override;
use Thecyrilcril\ImageKitClient\Time\Clock;

/**
 * Always the same instant, so a signed URL with an expiry is reproducible.
 */
final class FakeClock implements Clock
{
    public function __construct(public DateTimeImmutable $now) {}

    #[Override]
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
