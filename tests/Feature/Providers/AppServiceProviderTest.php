<?php

declare(strict_types=1);

use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\Shared\Sms\Gateways\LogGateway;
use App\Domain\User\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Tests\Fixtures\Gateways\FakeGateway;

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

test('an open page that needs a verified email sends the user to verify it once the email is unverified', function (bool $verified, int $status, ?string $location): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $page = $this->get(route('security.edit'));

    if (! $verified) {
        $user->forceFill(['email_verified_at' => null])->save();
    }

    $response = callLivewireAction($page, 'settings.security', 'closeModal');

    $response->assertStatus($status);

    expect($response->headers->get('Location'))->toBe($location);
})->with([
    'a verified email' => [true, 200, null],
    'an unverified email' => [false, 302, fn (): string => route('verification.notice')],
]);

test('an open page that needs a verified phone number sends the user to verify it once the number is unverified', function (bool $verified, int $status, ?string $location): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $page = $this->get(route('security.edit'));

    if (! $verified) {
        $user->forceFill(['phone_verified_at' => null])->save();
    }

    $response = callLivewireAction($page, 'settings.security', 'closeModal');

    $response->assertStatus($status);

    expect($response->headers->get('Location'))->toBe($location);
})->with([
    'a verified phone number' => [true, 200, null],
    'an unverified phone number' => [false, 302, fn (): string => route('phone-verification.notice')],
]);

test('the kit sends sms through the log gateway by default', function (): void {
    expect(resolve(SmsGateway::class))->toBeInstanceOf(LogGateway::class);
});

test('the sms gateway is the class that system.sms.gateways lists under the configured name', function (): void {
    config(['system.sms.gateways.fake' => FakeGateway::class, 'system.sms.gateway' => 'fake']);

    expect(resolve(SmsGateway::class))->toBeInstanceOf(FakeGateway::class);
});

test('an sms gateway name that system.sms.gateways does not list is refused', function (): void {
    config(['system.sms.gateway' => 'acme']);

    expect(fn () => resolve(SmsGateway::class))->toThrow(LogicException::class, 'The SMS gateway [acme] is not in system.sms.gateways.');
});

test('a configured sms gateway that does not implement SmsGateway is refused', function (): void {
    config(['system.sms.gateways.log' => stdClass::class]);

    expect(fn () => resolve(SmsGateway::class))->toThrow(LogicException::class, 'system.sms.gateways.log must name a class that implements SmsGateway.');
});
