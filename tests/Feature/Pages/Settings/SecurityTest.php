<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Settings\Security;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
    Features::passkeys([
        'confirmPassword' => true,
    ]);
});

test('security settings page can be rendered', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'));

    $response->assertOk();

    $response->assertSee('Passkeys');
    $response->assertSee('No passkeys yet');
    $response->assertSee('Two-factor authentication');
    $response->assertSee('Enable 2FA');
});

test('security settings page requires password confirmation when enabled', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('security.edit'));

    $response->assertRedirect(route('password.confirm'));
});

test('security settings page renders without two factor when feature is disabled', function (): void {
    config(['fortify.features' => []]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('Update password')
        ->assertDontSee('Manage your passkeys for passwordless sign-in')
        ->assertDontSee('No passkeys yet')
        ->assertDontSee('Two-factor authentication');
});

test('two factor authentication disabled when confirmation abandoned between requests', function (): void {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    $component = Livewire::test(Security::class);

    $component->assertSet('twoFactorEnabled', false);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
    ]);
});

test('password can be updated', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test(Security::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test(Security::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasErrors(['current_password']);
});

test('an open security page asks for the password again after the confirmation expires', function (string $method): void {
    $user = User::factory()->create();
    $user->forceFill(['two_factor_secret' => encrypt('test-secret'), 'two_factor_recovery_codes' => encrypt(json_encode(['code1'])), 'two_factor_confirmed_at' => now()])->save();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time() - config()->integer('auth.password_timeout') - 1]);

    Livewire::test(Security::class)
        ->call($method)
        ->assertRedirect(route('password.confirm'));

    expect($user->refresh()->two_factor_secret)->not->toBeNull();
})->with(['disable', 'enable']);

test('a recently confirmed password lets the security page disable two factor', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['two_factor_secret' => encrypt('test-secret'), 'two_factor_recovery_codes' => encrypt(json_encode(['code1'])), 'two_factor_confirmed_at' => now()])->save();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

    Livewire::test(Security::class)->call('disable')->assertNoRedirect();

    expect($user->refresh()->two_factor_secret)->toBeNull();
});
