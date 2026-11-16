<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;
use Tests\Fixtures\Livewire\VerifiedPanel;
use Tests\Fixtures\Livewire\VerifiedPanelHost;

beforeEach(function (): void {
    VerifiedPanel::$runs = 0;
});

test('a verified user sees the content', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(VerifiedPanel::class)
        ->assertOk()
        ->assertSee('Protected content');
});

test('a user with an unverified email goes to the email verification page', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);

    $this->actingAs(User::factory()->unverified()->create());

    Livewire::test(VerifiedPanel::class)
        ->assertStatus(302)
        ->assertHeader('Location', route('verification.notice'));
});

test('a user with an unverified phone number goes to the phone verification page', function (): void {
    $this->skipUnlessFeature(Feature::PhoneVerification);

    $this->actingAs(User::factory()->unverifiedPhone()->create());

    Livewire::test(VerifiedPanel::class)
        ->assertStatus(302)
        ->assertHeader('Location', route('phone-verification.notice'));
});

test('the email check comes before the phone check', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);
    $this->skipUnlessFeature(Feature::PhoneVerification);

    $this->actingAs(User::factory()->unverified()->unverifiedPhone()->create());

    Livewire::test(VerifiedPanel::class)
        ->assertStatus(302)
        ->assertHeader('Location', route('verification.notice'));
});

test('a holder of bypass-authorization sees the content without verification', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);
    $this->skipUnlessFeature(Feature::PhoneVerification);

    $this->seed(PermissionSeeder::class);
    $user = User::factory()->unverified()->unverifiedPhone()->create();
    grantPermissions($user, 'bypass-authorization');
    $this->actingAs($user);

    Livewire::test(VerifiedPanel::class)
        ->assertOk()
        ->assertSee('Protected content');
});

test('a request that expects JSON from a user with an unverified email gets 403, and no code of the component runs', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);

    $this->actingAs(User::factory()->unverified()->create());

    Livewire::withHeaders(['Accept' => 'application/json'])
        ->test(VerifiedPanel::class)
        ->assertForbidden();

    expect(VerifiedPanel::$runs)->toBe(0);
});

test('a user with an unverified email goes to the verification page from a component that mounts inside an update', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);

    $this->actingAs(User::factory()->unverified()->create());
    $host = Livewire::test(VerifiedPanelHost::class);

    $host->call('show')
        ->assertStatus(302)
        ->assertHeader('Location', route('verification.notice'));

    expect(VerifiedPanel::$runs)->toBe(0);
});

test('a user who becomes unverified goes to the verification page on the next update', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);

    $user = User::factory()->create();
    $this->actingAs($user);
    $panel = Livewire::test(VerifiedPanel::class);
    $user->forceFill(['email_verified_at' => null])->save();

    $panel->call('$refresh')
        ->assertStatus(302)
        ->assertHeader('Location', route('verification.notice'));
});

test('a user with an unverified email goes to the verification page from a lazy component, and no code of the component runs', function (string $mode, string $method, array $arguments): void {
    $this->skipUnlessFeature(Feature::EmailVerification);

    $this->actingAs(User::factory()->unverified()->create());
    $panel = Livewire::test(VerifiedPanel::class, [$mode => true])->assertDontSee('Protected content');

    $panel->{$method}(...$arguments)
        ->assertStatus(302)
        ->assertHeader('Location', route('verification.notice'));

    expect(VerifiedPanel::$runs)->toBe(0);
})->with([
    'lazy, a method call' => ['lazy', 'call', ['ping']],
    'lazy, a property update' => ['lazy', 'set', ['note', 'changed']],
    'lazy, a refresh' => ['lazy', 'call', ['$refresh']],
    'lazy, an event' => ['lazy', 'dispatch', ['poke']],
    'defer, a method call' => ['defer', 'call', ['ping']],
    'defer, an event' => ['defer', 'dispatch', ['poke']],
]);

test('a verified user sees the content of a lazy component after it loads', function (): void {
    $this->actingAs(User::factory()->create());
    $panel = Livewire::test(VerifiedPanel::class, ['lazy' => true])->assertDontSee('Protected content');
    $encodedParams = str($panel->html())->between('__lazyLoad(&#039;', '&#039;)')->value();

    $panel->call('__lazyLoad', $encodedParams)
        ->assertOk()
        ->assertSee('Protected content');
});

test('a middleware answer that is not a pass stops the request', function (): void {
    app()->bind(EnsureEmailIsVerified::class, fn (): object => new class
    {
        public function handle(Request $request, Closure $next): Response
        {
            return response('Denied', 403);
        }
    });
    $this->actingAs(User::factory()->create());

    Livewire::test(VerifiedPanel::class)
        ->assertForbidden();

    expect(VerifiedPanel::$runs)->toBe(0);
});
