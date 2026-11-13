<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\User\PasskeyDataTable;
use Filament\Actions\Testing\TestAction;
use Laravel\Passkeys\Passkey;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);

    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.users.view', 'internal.controls.users.update');
    $this->actingAs($this->actor);

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

beforeEach(function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);
});

test('the passkeys tab lists the passkeys of the user', function (): void {
    $own = createPasskey($this->subject, 'Laptop');
    $other = createPasskey($this->actor, 'Phone');

    passkeyTable($this->subject)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$other])
        ->assertSee('Laptop');
});

test('a passkey can be revoked on the page of another user', function (): void {
    $passkey = createPasskey($this->subject);

    passkeyTable($this->subject)->callAction(TestAction::make('revoke')->table($passkey));

    expect(Passkey::query()->find($passkey->id))->toBeNull();
});

test('revoke is hidden on your own page', function (): void {
    $passkey = createPasskey($this->actor);

    passkeyTable($this->actor)
        ->assertCanSeeTableRecords([$passkey])
        ->assertActionHidden(TestAction::make('revoke')->table($passkey));
});

test('revoke is hidden without update', function (): void {
    $passkey = createPasskey($this->subject);
    revokePermissions($this->actor, 'internal.controls.users.update');

    passkeyTable($this->subject)
        ->assertCanSeeTableRecords([$passkey])
        ->assertActionHidden(TestAction::make('revoke')->table($passkey));
});
