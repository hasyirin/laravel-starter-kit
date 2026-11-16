<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Settings\DeleteUserForm;
use Livewire\Livewire;

test('user can delete their account', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(DeleteUserForm::class)
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

    $response = Livewire::test(DeleteUserForm::class)
        ->set('password', 'wrong-password')
        ->call('delete');

    $response->assertHasErrors(['password']);

    expect($user->fresh()?->trashed())->toBeFalse();
    expect(auth()->check())->toBeTrue();
});

test('the account tab shows and opens for a user with an unverified email', function (): void {
    $this->actingAs(User::factory()->unverified()->create());

    $response = $this->get(route('settings', ['tab' => 'account']));

    $response->assertOk()
        ->assertSee('data-tab="account"', false)
        ->assertSeeLivewire(DeleteUserForm::class);
});

test('a user with an unverified email can delete their account', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    $response = Livewire::test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('delete');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh()?->trashed())->toBeTrue();
    expect(auth()->check())->toBeFalse();
});
