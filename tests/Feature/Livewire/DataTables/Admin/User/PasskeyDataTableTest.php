<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\User\PasskeyDataTable;
use Filament\Actions\Testing\TestAction;
use Laravel\Passkeys\Passkey;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create();
    grantPermissions($this->admin, 'admin.access', 'admin.controls.users.view', 'admin.controls.users.update');
    $this->actingAs($this->admin);

    $this->subject = User::factory()->create();
});

function createPasskey(User $user, string $name = 'Laptop'): Passkey
{
    return $user->passkeys()->create([
        'name' => $name,
        'credential_id' => str()->random(32),
        'credential' => ['id' => 'credential'],
    ]);
}

function passkeyTable(User $user): Testable
{
    return Livewire::test(PasskeyDataTable::class, ['user' => $user]);
}

test('the passkeys tab lists the passkeys of the user', function (): void {
    $own = createPasskey($this->subject, 'Laptop');
    $other = createPasskey($this->admin, 'Phone');

    passkeyTable($this->subject)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$other])
        ->assertSee('Laptop');
});

test('an admin can revoke a passkey', function (): void {
    $passkey = createPasskey($this->subject);

    passkeyTable($this->subject)->callAction(TestAction::make('revoke')->table($passkey));

    expect(Passkey::query()->find($passkey->id))->toBeNull();
});

test('revoke is hidden on your own page', function (): void {
    $passkey = createPasskey($this->admin);

    passkeyTable($this->admin)
        ->assertCanSeeTableRecords([$passkey])
        ->assertActionHidden(TestAction::make('revoke')->table($passkey));
});

test('revoke is hidden without update', function (): void {
    $passkey = createPasskey($this->subject);
    revokePermissions($this->admin, 'admin.controls.users.update');

    passkeyTable($this->subject)
        ->assertCanSeeTableRecords([$passkey])
        ->assertActionHidden(TestAction::make('revoke')->table($passkey));
});
