<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Settings\Security;
use App\Livewire\Settings\TwoFactor\RecoveryCodes;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

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

test('the security page lists the passkeys of the user', function (): void {
    $user = User::factory()->create();
    $user->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => str()->random(32),
        'credential' => ['id' => 'credential'],
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('Work laptop')
        ->assertDontSee('No passkeys yet');
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

test('enabling two factor shows the setup for a new secret', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

    $component = Livewire::test(Security::class)->call('enable');

    $component
        ->assertHasNoErrors()
        ->assertSet('showingSetupModal', true)
        ->assertSet('manualSetupKey', decrypt($user->refresh()->two_factor_secret))
        ->assertNotSet('qrCodeSvg', '')
        ->assertSet('twoFactorEnabled', false);
});

test('a valid code turns two factor on', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $component = Livewire::test(Security::class)->call('enable');
    $code = resolve(Google2FA::class)->getCurrentOtp(decrypt($user->refresh()->two_factor_secret));

    $component->set('code', $code)->call('confirmTwoFactor');

    $component
        ->assertHasNoErrors()
        ->assertSet('showingSetupModal', false)
        ->assertSet('twoFactorEnabled', true);
    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

test('a code that does not match the secret leaves two factor off', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $component = Livewire::test(Security::class)->call('enable');

    $component->set('code', 'abcdef')->call('confirmTwoFactor');

    $component
        ->assertHasErrors(['code' => 'The provided two factor authentication code was invalid.'])
        ->assertSet('twoFactorEnabled', false);
    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('confirming two factor asks for the password again after the confirmation expires', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $component = Livewire::test(Security::class)->call('enable');
    $code = resolve(Google2FA::class)->getCurrentOtp(decrypt($user->refresh()->two_factor_secret));
    session()->put('auth.password_confirmed_at', time() - config()->integer('auth.password_timeout') - 1);

    $component->set('code', $code)->call('confirmTwoFactor');

    $component->assertRedirect(route('password.confirm'));
    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('removing a passkey deletes it and drops it from the list', function (): void {
    $user = User::factory()->create();
    $passkey = $user->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => str()->random(32),
        'credential' => ['id' => 'credential'],
    ]);
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $component = Livewire::test(Security::class)->call('confirmDelete', $passkey->id);

    $component->call('deletePasskey');

    $component
        ->assertSet('showingDeleteModal', false)
        ->assertDontSee('Work laptop');
    $this->assertModelMissing($passkey);
});

test('removing a passkey asks for the password again after the confirmation expires', function (): void {
    $user = User::factory()->create();
    $passkey = $user->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => str()->random(32),
        'credential' => ['id' => 'credential'],
    ]);
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time() - config()->integer('auth.password_timeout') - 1]);
    $component = Livewire::test(Security::class)->call('confirmDelete', $passkey->id);

    $component->call('deletePasskey');

    $component->assertRedirect(route('password.confirm'));
    $this->assertModelExists($passkey);
});

test('removing the passkey of another user gives 404', function (): void {
    $passkey = User::factory()->create()->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => str()->random(32),
        'credential' => ['id' => 'credential'],
    ]);
    $this->actingAs(User::factory()->create())->withSession(['auth.password_confirmed_at' => time()]);

    $component = Livewire::test(Security::class)->call('confirmDelete', $passkey->id);

    $component->assertNotFound();
    $this->assertModelExists($passkey);
});

test('regenerating recovery codes replaces the old codes', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

    $component = Livewire::test(RecoveryCodes::class)->call('regenerate');

    $component->assertSet('recoveryCodes', $user->refresh()->recoveryCodes());

    expect($user->recoveryCodes())->not->toContain('recovery-code-1');
});

test('regenerating recovery codes asks for the password again after the confirmation expires', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time() - config()->integer('auth.password_timeout') - 1]);

    $component = Livewire::test(RecoveryCodes::class)->call('regenerate');

    $component->assertRedirect(route('password.confirm'));

    expect($user->refresh()->recoveryCodes())->toBe(['recovery-code-1']);
});
