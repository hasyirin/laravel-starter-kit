<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Settings\UserInformation;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('the user information page is displayed', function (): void {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/user-information')->assertOk();
});

test('user information can be updated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(UserInformation::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('save');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(UserInformation::class)
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('save');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('delete');

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
        ->call('delete');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('the delete account form shows only once the email is verified', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    Livewire::test(UserInformation::class)->assertDontSeeLivewire('settings.delete-user-form');

    $user->markEmailAsVerified();

    Livewire::test(UserInformation::class)->assertSeeLivewire('settings.delete-user-form');
});

test('the email is stored in lowercase', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(UserInformation::class)
        ->set('email', 'Alice@Example.COM')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::query()->where('email', 'alice@example.com')->exists())->toBeTrue()
        ->and(User::factory()->create(['email' => 'Bob@Example.COM'])->email)->toBe('bob@example.com');
});

test('an email that differs from another user only in case is taken', function (): void {
    User::factory()->create(['email' => 'bob@example.com']);
    $this->actingAs(User::factory()->create());

    Livewire::test(UserInformation::class)
        ->set('email', 'Bob@Example.com')
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);
});

test('the verification email can be sent six times a minute', function (): void {
    Notification::fake();
    $this->actingAs($user = User::factory()->unverified()->create());

    $component = Livewire::test(UserInformation::class);

    foreach (range(1, 7) as $attempt) {
        $component->call('resendVerificationNotification');
    }

    Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    $component->assertDispatched('toast-show', fn (string $name, array $params): bool => str_starts_with($params['slots']['text'], 'Too many verification emails.'));
});
