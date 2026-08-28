<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Http;

use Illuminate\Support\Sleep;
use Override;

/**
 * Really waits. Goes through Illuminate's Sleep so Sleep::fake() can
 * observe it too.
 */
final class SystemSleeper implements Sleeper
{
    #[Override]
    public function sleep(int $milliseconds): void
    {
        Sleep::for($milliseconds)->milliseconds();
    }
}
