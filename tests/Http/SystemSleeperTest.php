<?php

declare(strict_types=1);

use Carbon\CarbonInterval;
use Illuminate\Support\Sleep;
use Thecyrilcril\ImageKitClient\Http\Sleeper;
use Thecyrilcril\ImageKitClient\Http\SystemSleeper;

it('is the Sleeper the service provider binds', function (): void {
    $this->app->forgetInstance(Sleeper::class);

    expect(app(Sleeper::class))->toBeInstanceOf(SystemSleeper::class);
});

it('waits the given number of milliseconds', function (): void {
    Sleep::fake();

    (new SystemSleeper)->sleep(1500);

    Sleep::assertSlept(fn (CarbonInterval $duration): bool => $duration->totalMilliseconds === 1500.0);
});
