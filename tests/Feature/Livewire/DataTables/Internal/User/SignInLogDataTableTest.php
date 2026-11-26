<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\User\SignInLogDataTable;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.users.view', 'internal.controls.users.view-sign-in-log');
    $this->actingAs($this->actor);
    $this->subject = User::factory()->create();
});

test('the sign-in log tab lists the entries of the user and the entries the user made as an impersonator', function (): void {
    $own = SignInEntry::factory()->forUser($this->subject)->create();
    $impersonated = SignInEntry::factory()->impersonationStarted()->forUser($this->subject)->create();
    $asImpersonator = SignInEntry::factory()->impersonationStarted()->create(['impersonator_id' => $this->subject->id]);
    $other = SignInEntry::factory()->forUser($this->actor)->create();

    Livewire::test(SignInLogDataTable::class, ['user' => $this->subject])
        ->assertCanSeeTableRecords([$own, $impersonated, $asImpersonator])
        ->assertCanNotSeeTableRecords([$other]);
});

test('the sign-in log tab needs view-sign-in-log', function (): void {
    revokePermissions($this->actor, 'internal.controls.users.view-sign-in-log');

    Livewire::test(SignInLogDataTable::class, ['user' => $this->subject])->assertForbidden();
});
