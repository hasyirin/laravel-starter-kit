<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

test('booting in production with debug mode on turns debug mode off and throws', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config(['app.debug' => true]);

    expect(new AppServiceProvider(app())->boot(...))->toThrow(RuntimeException::class, 'Debug mode must be off in production. Set APP_DEBUG=false.')
        ->and(config('app.debug'))->toBeFalse();
});

test('the app boots when debug mode is off or the environment is not production', function (string $environment, bool $debug): void {
    app()->detectEnvironment(fn (): string => $environment);
    config(['app.debug' => $debug]);

    new AppServiceProvider(app())->boot();

    expect(config('app.debug'))->toBe($debug);
})->with([
    ['production', false],
    ['local', true],
]);

test('scheduled tasks run on the clock of the business, not in the time zone of the app', function (): void {
    expect(resolve(Schedule::class)->command('inspire')->timezone)->toBe(config('system.timezone'));
});
