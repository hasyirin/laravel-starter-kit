<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
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

test('an open page that needs a verified email sends the user to verify it once the email is unverified', function (bool $verified, ?string $location): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    preg_match('/wire:snapshot="([^"]+)"/', (string) $this->get(route('security.edit'))->getContent(), $match);

    if (! $verified) {
        $user->forceFill(['email_verified_at' => null])->save();
    }

    $payload = json_encode(['components' => [['snapshot' => html_entity_decode($match[1]), 'updates' => [], 'calls' => [['method' => 'closeModal', 'params' => [], 'metadata' => []]]]]]);
    $response = $this->call('POST', resolve('livewire')->getUpdateUri(), server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => '*/*', 'HTTP_X_LIVEWIRE' => '1'], content: $payload);

    expect($response->headers->get('Location'))->toBe($location);
})->with([
    'a verified email' => [true, null],
    'an unverified email' => [false, fn (): string => route('verification.notice')],
]);
