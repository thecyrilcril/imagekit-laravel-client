<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Time;

use DateTimeImmutable;
use Illuminate\Support\Facades\Date;
use Override;

/**
 * The real time. Goes through Illuminate's Date so travelTo() and
 * Carbon::setTestNow() are honoured too.
 */
final class SystemClock implements Clock
{
    #[Override]
    public function now(): DateTimeImmutable
    {
        return Date::now()->toImmutable();
    }
}
