<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->user = User::factory()->create();
});

test('adding a department, branch, or position needs organization update', function (string $model): void {
    expect($this->user->can('create', $model))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.organizations.update');

    expect($this->user->can('create', $model))->toBeTrue();
})->with([Department::class, Branch::class, Position::class]);

test('updating and deleting a department, branch, or position needs organization update', function (string $model): void {
    $record = $model::factory()->create();

    expect($this->user->can('update', $record))->toBeFalse()
        ->and($this->user->can('delete', $record))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.organizations.update');

    expect($this->user->can('update', $record))->toBeTrue()
        ->and($this->user->can('delete', $record))->toBeTrue();
})->with([Department::class, Branch::class, Position::class]);

test('the owner department and owner branch cannot be updated or deleted', function (string $model): void {
    grantPermissions($this->user, 'internal.controls.organizations.update', 'internal.controls.organizations.delete');

    $owner = $model::owner();

    expect($this->user->can('update', $owner))->toBeFalse()
        ->and($this->user->can('delete', $owner))->toBeFalse();
})->with([Department::class, Branch::class]);

test('records under the owner organization can be updated', function (): void {
    grantPermissions($this->user, 'internal.controls.organizations.update');

    $department = Department::factory()->for(Organization::owner())->create();

    expect($this->user->can('update', $department))->toBeTrue();
});

test('viewing a department, branch, or position needs organization view', function (string $model, string $permission): void {
    $record = $model::factory()->create();

    expect($this->user->can('view', $record))->toBeFalse();

    grantPermissions($this->user, $permission);

    expect($this->user->can('view', $record))->toBeTrue();
})->with([Department::class, Branch::class, Position::class])
    ->with(['internal.controls.organizations.view-any', 'internal.controls.organizations.view']);

test('viewing the audit log of a department or branch needs view-audit-log', function (string $model): void {
    $record = $model::factory()->create();
    grantPermissions($this->user, 'internal.controls.organizations.view');

    expect($this->user->can('view', [$record, 'audit-log']))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.organizations.view-audit-log');

    expect($this->user->can('view', [$record, 'audit-log']))->toBeTrue();
})->with([Department::class, Branch::class]);
