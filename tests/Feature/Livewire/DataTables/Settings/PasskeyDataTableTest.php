<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Settings\PasskeyDataTable;
use App\Livewire\Settings\PasskeyModal;
use Filament\Actions\Testing\TestAction;
use Laravel\Passkeys\Passkey;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function createPasskeyFor(User $user, string $name = 'Laptop'): Passkey
{
    return $user->passkeys()->create([
        'name' => $name,
        'credential_id' => str()->random(32),
        'credential' => ['id' => 'credential'],
    ]);
}

test('the table lists only the passkeys of the user', function (): void {
    $own = createPasskeyFor($this->user, 'Laptop');
    $other = createPasskeyFor(User::factory()->create(), 'Phone');

    Livewire::test(PasskeyDataTable::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$other])
        ->assertSee('Laptop')
        ->assertDontSee('Phone');
});

test('the table shows the empty heading without passkeys', function (): void {
    Livewire::test(PasskeyDataTable::class)->assertSee(__('user/passkeys.empty.heading'));
});

test('create opens the passkey dialog', function (): void {
    Livewire::test(PasskeyDataTable::class)
        ->callAction(TestAction::make('create')->table())
        ->assertDispatchedTo(PasskeyModal::class, 'show');
});

test('remove deletes the passkey after a recent password confirmation', function (): void {
    $passkey = createPasskeyFor($this->user);
    $this->withSession(['auth.password_confirmed_at' => time()]);

    Livewire::test(PasskeyDataTable::class)
        ->callAction(TestAction::make('remove')->table($passkey))
        ->assertNoRedirect();

    $this->assertModelMissing($passkey);
});

test('remove asks for the password again and keeps the passkey after the confirmation expires', function (): void {
    $passkey = createPasskeyFor($this->user);
    $this->withSession(['auth.password_confirmed_at' => time() - config()->integer('auth.password_timeout') - 1]);

    Livewire::test(PasskeyDataTable::class)
        ->callAction(TestAction::make('remove')->table($passkey))
        ->assertRedirect(route('password.confirm'));

    $this->assertModelExists($passkey);
});

test('the passkey of another user cannot be removed', function (): void {
    $passkey = createPasskeyFor(User::factory()->create());
    $this->withSession(['auth.password_confirmed_at' => time()]);

    Livewire::test(PasskeyDataTable::class)
        ->mountAction(TestAction::make('remove')->table($passkey))
        ->assertActionNotMounted()
        ->call('callMountedAction');

    $this->assertModelExists($passkey);
});

test('the table refreshes after the dialog adds a passkey', function (): void {
    $table = Livewire::test(PasskeyDataTable::class)->assertCountTableRecords(0);
    $passkey = createPasskeyFor($this->user);

    $table->dispatch(PasskeyModal::event())->assertCanSeeTableRecords([$passkey]);
});

test('a user with an unverified email goes to the verification page instead of the table', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);

    $this->actingAs(User::factory()->unverified()->create());

    Livewire::test(PasskeyDataTable::class)
        ->assertStatus(302)
        ->assertHeader('Location', route('verification.notice'));
});
