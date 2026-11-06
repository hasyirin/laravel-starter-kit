<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\StartImpersonation;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->impersonator = User::factory()->create();
    $this->user = User::factory()->create();
    grantPermissions($this->impersonator, 'internal.controls.users.impersonate', 'internal.controls.users.update');

    $this->actingAs($this->impersonator);
});

test('starting signs in as the impersonated user', function (): void {
    StartImpersonation::make()->execute($this->impersonator, $this->user, 'https://example.test/start');

    expect(auth()->id())->toBe($this->user->id)
        ->and(Impersonation::impersonatorId())->toBe($this->impersonator->id)
        ->and(Impersonation::startUrl())->toBe('https://example.test/start');
});

test('starting regenerates the session id', function (): void {
    $id = session()->getId();

    StartImpersonation::make()->execute($this->impersonator, $this->user, 'https://example.test/start');

    expect(session()->getId())->not->toBe($id);
});

test('starting forgets the password confirmation and the intended url', function (): void {
    session()->put(['auth.password_confirmed_at' => now()->unix(), 'url.intended' => 'https://example.test/intended']);

    StartImpersonation::make()->execute($this->impersonator, $this->user, 'https://example.test/start');

    expect(session()->has('auth.password_confirmed_at'))->toBeFalse()
        ->and(session()->has('url.intended'))->toBeFalse();
});

test('starting does not remember the impersonated user', function (): void {
    StartImpersonation::make()->execute($this->impersonator, $this->user, 'https://example.test/start');

    $rememberCookies = collect(cookie()->getQueuedCookies())->filter(fn ($cookie): bool => str($cookie->getName())->startsWith('remember_'));

    expect(auth()->viaRemember())->toBeFalse()
        ->and($rememberCookies)->toBeEmpty();
});

test('starting is refused without the impersonate permission', function (): void {
    revokePermissions($this->impersonator, 'internal.controls.users.impersonate');

    expect(fn () => StartImpersonation::make()->execute($this->impersonator, $this->user, 'https://example.test/start'))
        ->toThrow(AuthorizationException::class);
    expect(auth()->id())->toBe($this->impersonator->id);
});

test('starting is refused while impersonating', function (): void {
    StartImpersonation::make()->execute($this->impersonator, $this->user, 'https://example.test/start');

    expect(fn () => StartImpersonation::make()->execute($this->impersonator, User::factory()->create(), 'https://example.test/start'))
        ->toThrow(AuthorizationException::class);
});

test('a user with bypass-authorization cannot impersonate themselves', function (): void {
    grantPermissions($this->impersonator, 'bypass-authorization');

    expect(fn () => StartImpersonation::make()->execute($this->impersonator, $this->impersonator, 'https://example.test/start'))
        ->toThrow(AuthorizationException::class);
});

test('a user with bypass-authorization cannot start a nested impersonation', function (): void {
    grantPermissions($this->impersonator, 'bypass-authorization');
    grantPermissions($this->user, 'bypass-authorization');
    StartImpersonation::make()->execute($this->impersonator, $this->user, 'https://example.test/start');

    expect(fn () => StartImpersonation::make()->execute($this->user, User::factory()->create(), 'https://example.test/other'))
        ->toThrow(AuthorizationException::class);
    expect(Impersonation::impersonatorId())->toBe($this->impersonator->id);
});

test('a user with bypass-authorization cannot impersonate a deleted user', function (): void {
    grantPermissions($this->impersonator, 'bypass-authorization');
    $this->user->delete();

    expect(fn () => StartImpersonation::make()->execute($this->impersonator, $this->user, 'https://example.test/start'))
        ->toThrow(AuthorizationException::class);
});
