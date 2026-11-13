<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\Shared\Sms\Gateways\LogGateway;
use App\Domain\User\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
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

test('an API client gets 429 once it makes more calls in a minute than its own rate limit', function (): void {
    $token = createApiToken(ApiClient::factory()->anyIpAddress()->create(['rate_limit_per_minute' => 2]));
    $this->withToken($token)->getJson('/api/v1/ping')->assertOk()->assertHeader('X-RateLimit-Limit', '2');
    $this->withToken($token)->getJson('/api/v1/ping')->assertOk();

    $response = $this->withToken($token)->getJson('/api/v1/ping');

    $response->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 429);
});

test('the rate limit counts the calls of each API client apart', function (): void {
    $busy = createApiToken(ApiClient::factory()->anyIpAddress()->create(['rate_limit_per_minute' => 1]));
    $other = createApiToken(ApiClient::factory()->anyIpAddress()->create(['rate_limit_per_minute' => 1]));
    $this->withToken($busy)->getJson('/api/v1/ping')->assertOk();

    $this->withToken($other)->getJson('/api/v1/ping')->assertOk();
});

test('the service entry point sends to the base URL of the service with its timeouts and the trace ID, and adds no credentials', function (): void {
    config(['system.api.services.billing' => ['base_url' => 'https://billing.test', 'timeout' => 5, 'connect_timeout' => 2, 'retry' => 1, 'log_bodies' => false]]);
    context()->add('trace_id', 'trace-123');
    Http::fake();

    $pendingRequest = Http::service('billing');
    $pendingRequest->get('/v1/status');

    expect($pendingRequest->getOptions())->toMatchArray(['timeout' => 5, 'connect_timeout' => 2]);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://billing.test/v1/status'
        && $request->header('X-Request-Id') === ['trace-123']
        && ! $request->hasHeader('Authorization'));
});

test('a service name that system.api.services does not list is refused', function (): void {
    expect(fn () => Http::service('acme'))->toThrow(LogicException::class, 'The service [acme] is not in system.api.services.');
});
