<?php

declare(strict_types=1);

namespace Thecyrilcril\ImageKitClient\Tests\Support;

use Override;
use Thecyrilcril\ImageKitClient\Http\Sleeper;

/**
 * Records every requested wait and never actually sleeps.
 */
final class FakeSleeper implements Sleeper
{
    /**
     * @var list<int>
     */
    public array $slept = [];

    #[Override]
    public function sleep(int $milliseconds): void
    {
        $this->slept[] = $milliseconds;
    }
}
