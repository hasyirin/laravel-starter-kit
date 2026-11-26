<?php

declare(strict_types=1);

use App\Domain\User\Actions\Security\UpdatePassword;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use Illuminate\Auth\Events\Logout;

test('a sign-out deletes the session row and writes no session-ended entry', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $this->post(route('logout'));

    $this->assertGuest();
    expect(UserSession::withTrashed()->count())->toBe(0)
        ->and(SignInEntry::query()->pluck('event')->all())->toBe([SignInEvent::SignOut]);
});

test('a session that auth.session signs out after a password change loses its row and writes no entry', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    UpdatePassword::make()->execute(User::query()->findOrFail($user->id), 'new-password');

    $this->actingAs($user->fresh())->get(route('dashboard'))->assertRedirect(route('login'));

    expect(UserSession::withTrashed()->count())->toBe(0)
        ->and(SignInEntry::query()->count())->toBe(0);
});

test('a sign-out on another guard than the Fortify guard keeps the session row', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    event(new Logout('other', $user));

    expect(UserSession::query()->count())->toBe(1);
});
