<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\User;
use App\Livewire\Settings\PasskeyModal;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);

    $this->actingAs(User::factory()->create());
});

test('the dialog opens after a recent password confirmation', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);

    Livewire::test(PasskeyModal::class)
        ->call('show')
        ->assertNoRedirect()
        ->assertSet('showing', true)
        ->assertSee(__('user/passkeys.add.name'));
});

test('the dialog asks for the password again and stays closed after the confirmation expires', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time() - config()->integer('auth.password_timeout') - 1]);

    Livewire::test(PasskeyModal::class)
        ->call('show')
        ->assertRedirect(route('password.confirm'))
        ->assertSet('showing', false);
});

test('a registered passkey closes the dialog and tells the table', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);

    Livewire::test(PasskeyModal::class)
        ->call('show')
        ->call('registered')
        ->assertSet('showing', false)
        ->assertDispatched(PasskeyModal::event());
});

test('a user with an unverified email goes to the verification page instead of the dialog', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);

    $this->actingAs(User::factory()->unverified()->create());

    Livewire::test(PasskeyModal::class)
        ->assertStatus(302)
        ->assertHeader('Location', route('verification.notice'));
});

test('close hides the dialog and removes the form', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $modal = Livewire::test(PasskeyModal::class)->call('show');

    $modal->call('close');

    $modal->assertSet('showing', false)
        ->assertDontSee(__('user/passkeys.add.name'));
});
