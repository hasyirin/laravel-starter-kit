<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->letterhead = Letterhead::factory()->create();
});

test('each ability needs its own letterhead permission', function (string $ability, string $permission): void {
    $arguments = in_array($ability, ['viewAny', 'create'], true) ? Letterhead::class : $this->letterhead;

    expect($this->user->can($ability, $arguments))->toBeFalse();

    grantPermissions($this->user, "internal.controls.letterheads.$permission");

    expect($this->user->can($ability, $arguments))->toBeTrue();
})->with([
    ['viewAny', 'view-any'],
    ['view', 'view'],
    ['create', 'create'],
    ['update', 'update'],
    ['delete', 'delete'],
]);

test('another letterhead permission grants nothing', function (): void {
    grantPermissions($this->user, 'internal.controls.letterheads.view', 'internal.controls.organizations.update');

    expect($this->user->can('update', $this->letterhead))->toBeFalse()
        ->and($this->user->can('delete', $this->letterhead))->toBeFalse()
        ->and($this->user->can('create', Letterhead::class))->toBeFalse();
});

test('viewing the audit log needs its own permission', function (): void {
    grantPermissions($this->user, 'internal.controls.letterheads.view');

    expect($this->user->can('view', [$this->letterhead, 'audit-log']))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.letterheads.view-audit-log');

    expect($this->user->can('view', [$this->letterhead, 'audit-log']))->toBeTrue();
});
