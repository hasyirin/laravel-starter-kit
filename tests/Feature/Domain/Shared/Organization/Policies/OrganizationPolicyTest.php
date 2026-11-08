<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();
});

test('view any needs the view-any permission', function (): void {
    expect($this->user->can('viewAny', Organization::class))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.organizations.view-any');

    expect($this->user->can('viewAny', Organization::class))->toBeTrue();
});

test('view needs the view-any or view permission', function (string $permission): void {
    expect($this->user->can('view', $this->organization))->toBeFalse();

    grantPermissions($this->user, $permission);

    expect($this->user->can('view', $this->organization))->toBeTrue();
})->with(['internal.controls.organizations.view-any', 'internal.controls.organizations.view']);

test('viewing the audit log needs view-audit-log', function (): void {
    grantPermissions($this->user, 'internal.controls.organizations.view');

    expect($this->user->can('view', [$this->organization, 'audit-log']))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.organizations.view-audit-log');

    expect($this->user->can('view', [$this->organization, 'audit-log']))->toBeTrue();
});

test('create needs the create permission', function (): void {
    expect($this->user->can('create', Organization::class))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.organizations.create');

    expect($this->user->can('create', Organization::class))->toBeTrue();
});

test('update needs the update permission', function (): void {
    expect($this->user->can('update', $this->organization))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.organizations.update');

    expect($this->user->can('update', $this->organization))->toBeTrue();
});

test('delete needs the delete permission', function (): void {
    expect($this->user->can('delete', $this->organization))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.organizations.delete');

    expect($this->user->can('delete', $this->organization))->toBeTrue();
});

test('the owner organization cannot be updated or deleted, also with update and delete', function (): void {
    grantPermissions($this->user, 'internal.controls.organizations.update', 'internal.controls.organizations.delete');

    expect($this->user->can('update', Organization::owner()))->toBeFalse()
        ->and($this->user->can('delete', Organization::owner()))->toBeFalse();
});
