<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\Impersonation\StartImpersonation;
use App\Domain\User\Actions\Impersonation\StopImpersonation;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->impersonator = User::factory()->create();
    $this->user = User::factory()->create();
    grantPermissions($this->impersonator, 'internal.controls.users.impersonate', 'internal.controls.users.update');

    $this->actingAs($this->impersonator);
    StartImpersonation::make()->execute($this->impersonator, $this->user, 'https://example.test/start');
});

test('stopping signs the impersonator back in and returns the start url', function (): void {
    expect(StopImpersonation::make()->execute())->toBe('https://example.test/start')
        ->and(auth()->id())->toBe($this->impersonator->id)
        ->and(Impersonation::active())->toBeFalse();
});

test('stopping regenerates the session id', function (): void {
    $id = session()->getId();

    StopImpersonation::make()->execute();

    expect(session()->getId())->not->toBe($id);
});

test('stopping forgets the password confirmation and the intended url', function (): void {
    session()->put(['auth.password_confirmed_at' => now()->unix(), 'url.intended' => 'https://example.test/intended']);

    StopImpersonation::make()->execute();

    expect(session()->has('auth.password_confirmed_at'))->toBeFalse()
        ->and(session()->has('url.intended'))->toBeFalse();
});

test('stopping ends the session when the impersonator was deleted', function (): void {
    $this->impersonator->delete();

    expect(StopImpersonation::make()->execute())->toBeNull()
        ->and(auth()->guest())->toBeTrue()
        ->and(Impersonation::active())->toBeFalse();
});

test('stopping still works when the impersonator lost the permission', function (): void {
    revokePermissions($this->impersonator, 'internal.controls.users.impersonate');

    StopImpersonation::make()->execute();

    expect(auth()->id())->toBe($this->impersonator->id);
});

test('stopping is refused when not impersonating', function (): void {
    StopImpersonation::make()->execute();

    expect(fn (): ?string => StopImpersonation::make()->execute())->toThrow(AuthorizationException::class);
    expect(auth()->id())->toBe($this->impersonator->id);
});
