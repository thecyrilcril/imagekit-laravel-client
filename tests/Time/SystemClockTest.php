<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Thecyrilcril\ImageKitClient\Time\Clock;
use Thecyrilcril\ImageKitClient\Time\SystemClock;

it('is the Clock the service provider binds', function (): void {
    $this->app->forgetInstance(Clock::class);

    expect(app(Clock::class))->toBeInstanceOf(SystemClock::class);
});

it('reads the time Laravel travels to', function (): void {
    $this->travelTo(Carbon::createFromTimestampUTC(1_700_000_000));

    expect((new SystemClock)->now()->getTimestamp())->toBe(1_700_000_000);
});
