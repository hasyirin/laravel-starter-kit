<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Organization\UserDataTable;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view', 'internal.controls.users.view-any', 'internal.controls.users.view');
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create();
    $this->department = Department::factory()->for($this->organization)->create();
    $this->branch = Branch::factory()->for($this->organization)->create();
    $this->placed = User::factory()->create();
    Profile::factory()->for($this->placed)->create(['organization_id' => $this->organization->id, 'department_id' => $this->department->id]);
    $this->elsewhere = User::factory()->create();
    Profile::factory()->for($this->elsewhere)->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id]);
});

test('the list shows the users whose active profile is placed at the record', function (string $record, array $seen, array $unseen): void {
    Livewire::test(UserDataTable::class, ['record' => $this->{$record}])
        ->assertCanSeeTableRecords(collect($seen)->map(fn (string $user): User => $this->{$user}))
        ->assertCanNotSeeTableRecords(collect($unseen)->map(fn (string $user): User => $this->{$user}));
})->with([
    'organization' => ['organization', ['placed', 'elsewhere'], []],
    'department' => ['department', ['placed'], ['elsewhere']],
    'branch' => ['branch', ['elsewhere'], ['placed']],
]);

test('the list needs view on the record and view-any on users', function (string $permission): void {
    revokePermissions($this->actor, $permission);

    Livewire::test(UserDataTable::class, ['record' => $this->organization])->assertForbidden();
})->with(['internal.controls.organizations.view', 'internal.controls.users.view-any']);

test('the row click opens the user', function (): void {
    Livewire::test(UserDataTable::class, ['record' => $this->organization])
        ->callAction(TestAction::make('view')->table($this->placed))
        ->assertRedirect(route('internal.controls.users.show', $this->placed));
});
