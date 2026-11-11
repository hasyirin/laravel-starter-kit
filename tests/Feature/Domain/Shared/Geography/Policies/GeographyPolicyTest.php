<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->user = User::factory()->create();
});

dataset('geography models', [Country::class, State::class, District::class, Subdistrict::class, PostOffice::class, Postcode::class]);

test('view any and create need the geography permission of the same name', function (string $model, string $ability): void {
    expect($this->user->can($ability, $model))->toBeFalse();

    grantPermissions($this->user, "internal.controls.geography.$ability");

    expect($this->user->can($ability, $model))->toBeTrue();
})->with('geography models')->with(['viewAny' => 'view-any', 'create' => 'create']);

test('update and delete need the geography permission of the same name', function (string $model, string $ability): void {
    $record = $model::factory()->create();

    expect($this->user->can($ability, $record))->toBeFalse();

    grantPermissions($this->user, "internal.controls.geography.$ability");

    expect($this->user->can($ability, $record))->toBeTrue();
})->with('geography models')->with(['update', 'delete']);

test('view needs geography view-any or view', function (string $model, string $permission): void {
    $record = $model::factory()->create();

    expect($this->user->can('view', $record))->toBeFalse();

    grantPermissions($this->user, $permission);

    expect($this->user->can('view', $record))->toBeTrue();
})->with('geography models')->with(['internal.controls.geography.view-any', 'internal.controls.geography.view']);

test('viewing the audit log of a geography record needs view-audit-log', function (string $model): void {
    $record = $model::factory()->create();
    grantPermissions($this->user, 'internal.controls.geography.view');

    expect($this->user->can('view', [$record, 'audit-log']))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.geography.view-audit-log');

    expect($this->user->can('view', [$record, 'audit-log']))->toBeTrue();
})->with('geography models');
