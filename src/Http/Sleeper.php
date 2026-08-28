<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Http;

/**
 * How the Connection waits out a rate limit. Bound to SystemSleeper by the
 * service provider; tests bind a recording no-op so nothing actually waits.
 */
interface Sleeper
{
    public function sleep(int $milliseconds): void;
}
