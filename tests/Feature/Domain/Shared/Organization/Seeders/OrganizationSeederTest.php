<?php

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Organization\Seeders\OrganizationSeeder;

beforeEach(function (): void {
    config(['cache.stores.array.serialize' => true]);
    cache()->forgetDriver('array');
});

test('seeding creates the organization with its branches, departments, and positions', function (): void {
    $this->seed(OrganizationSeeder::class);

    $organization = Organization::query()->sole();
    $department = Department::query()->sole();

    expect($organization->code)->toBe('OWNER')
        ->and(Branch::query()->sole()->only('organization_id', 'code', 'name'))->toBe(['organization_id' => $organization->id, 'code' => 'HQ', 'name' => 'Headquarters'])
        ->and($department->only('organization_id', 'code', 'name'))->toBe(['organization_id' => $organization->id, 'code' => 'MAIN', 'name' => 'Main Department'])
        ->and(Position::query()->where('department_id', $department->id)->sole()->name)->toBe('Manager')
        ->and(Position::query()->whereNull('department_id')->sole()->only('organization_id', 'name'))->toBe(['organization_id' => $organization->id, 'name' => 'Chief Executive Officer']);
});

test('seeding twice does not duplicate anything', function (): void {
    $this->seed(OrganizationSeeder::class);
    $this->seed(OrganizationSeeder::class);

    expect(Organization::query()->count())->toBe(1)
        ->and(Branch::query()->count())->toBe(1)
        ->and(Department::query()->count())->toBe(1)
        ->and(Position::query()->count())->toBe(2);
});

test('seeding again after the owners are renamed creates nothing new', function (): void {
    $this->seed(OrganizationSeeder::class);

    Organization::owner()->update(['code' => 'RENAMED', 'name' => 'Renamed Organization']);
    Department::owner()->update(['code' => 'RENAMED']);
    Branch::owner()->update(['code' => 'RENAMED']);

    $this->seed(OrganizationSeeder::class);

    expect(Organization::query()->sole()->only('code', 'name'))->toBe(['code' => 'RENAMED', 'name' => 'Renamed Organization'])
        ->and(Department::query()->sole()->code)->toBe('RENAMED')
        ->and(Branch::query()->sole()->code)->toBe('RENAMED')
        ->and(Position::query()->count())->toBe(2);
});

test('seeding marks the owner organization, department, and branch', function (): void {
    $this->seed(OrganizationSeeder::class);

    expect(Organization::owner()->code)->toBe('OWNER')
        ->and(Department::owner()->code)->toBe('MAIN')
        ->and(Branch::owner()->code)->toBe('HQ');
});

test('seeding ignores owners cached before it ran, even without model events', function (): void {
    cache()->forever('system.owner.organization', ['id' => 999, 'code' => 'STALE', 'name' => 'Stale', 'is_owner' => true]);
    cache()->forever('system.owner.department', ['id' => 999, 'organization_id' => 999, 'code' => 'STALE', 'name' => 'Stale', 'is_owner' => true]);
    cache()->forever('system.owner.branch', ['id' => 999, 'organization_id' => 999, 'code' => 'STALE', 'name' => 'Stale', 'is_owner' => true]);

    Organization::withoutEvents(fn () => $this->seed(OrganizationSeeder::class));

    expect(Organization::owner()->code)->toBe('OWNER')
        ->and(Department::owner()->organization_id)->toBe(Organization::owner()->id)
        ->and(Branch::owner()->organization_id)->toBe(Organization::owner()->id)
        ->and(Organization::query()->whereKey(999)->exists())->toBeFalse();
});
