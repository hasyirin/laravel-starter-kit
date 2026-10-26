<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Settings\Profile;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

test('profile page is displayed', function (): void {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/profile')->assertOk();
});

test('profile information can be updated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('the time zone and locale start at the ones of the user, or else at the config', function (?string $timezone, string $expected): void {
    config(['system.timezone' => 'Asia/Kuala_Lumpur']);

    $this->actingAs(User::factory()->create(['timezone' => $timezone]));

    Livewire::test(Profile::class)->assertSet('timezone', $expected);
})->with([
    'a chosen time zone' => ['Asia/Tokyo', 'Asia/Tokyo'],
    'no time zone yet' => [null, 'Asia/Kuala_Lumpur'],
]);

test('the time zone and locale can be updated', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'en-MY']);

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('timezone', 'Asia/Tokyo')
        ->set('locale', 'en-GB')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect([$user->refresh()->timezone, $user->locale])->toBe(['Asia/Tokyo', 'en-GB']);
});

test('an unknown time zone or locale is rejected', function (string $field, string $value): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(Profile::class)
        ->set($field, $value)
        ->call('updateProfileInformation')
        ->assertHasErrors([$field]);
})->with([
    'time zone' => ['timezone', 'Mars/Olympus'],
    'locale' => ['locale', 'fr-FR'],
]);

test('the profile shows how dates look with the chosen time zone and locale', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 06:30', 'UTC'));
    $this->actingAs(User::factory()->create());

    Livewire::test(Profile::class)
        ->set('timezone', 'Asia/Kuala_Lumpur')
        ->set('locale', 'en-GB')
        ->assertSee('Dates and times look like this: 26 Oct 2026, 14:30');
});

test('user can delete their account', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh()?->trashed())->toBeTrue();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
