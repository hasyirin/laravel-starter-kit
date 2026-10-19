<?php

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function (): void {
    config(['cache.stores.array.serialize' => true]);
    cache()->forgetDriver('array');
});

function ownerOf(string $model): Organization|Department|Branch
{
    $owner = $model::factory()->create();
    $owner->forceFill(['is_owner' => true])->save();

    return $owner;
}

dataset('owner models', [Organization::class, Department::class, Branch::class]);

test('the owner is the record marked as owner', function (string $model): void {
    $model::factory()->create();
    $owner = ownerOf($model);

    expect($model::owner()->id)->toBe($owner->id);
})->with('owner models');

test('a new record is not the owner', function (string $model): void {
    expect($model::factory()->create()->is_owner)->toBeFalse();
})->with('owner models');

test('the owner organization is created from the config when there is none', function (): void {
    Organization::factory()->create();

    $owner = Organization::owner();

    expect($owner->only('code', 'name', 'is_owner'))->toBe(['code' => 'OWNER', 'name' => 'Owner Organization', 'is_owner' => true])
        ->and(Organization::query()->where('is_owner', true)->sole()->id)->toBe($owner->id);
});

test('the owner department and branch are created in the owner organization when there is none', function (string $model, string $code, string $name): void {
    $owner = $model::owner();

    expect($owner->only('organization_id', 'code', 'name', 'is_owner'))->toBe(['organization_id' => Organization::owner()->id, 'code' => $code, 'name' => $name, 'is_owner' => true]);
})->with([
    [Department::class, 'MAIN', 'Main Department'],
    [Branch::class, 'HQ', 'Headquarters'],
]);

test('a created owner has every column, also when cached', function (string $model): void {
    expect($model::owner()->trashed())->toBeFalse()
        ->and($model::owner()->trashed())->toBeFalse();
})->with('owner models');

test('the owner is created only once', function (string $model): void {
    $model::owner();
    $model::forgetOwner();
    $model::owner();

    expect($model::query()->where('is_owner', true)->count())->toBe(1);
})->with('owner models');

test('is_owner cannot be mass assigned', function (string $model): void {
    $model::query()->create([...$model::factory()->raw(), 'is_owner' => true]);
})->with('owner models')->throws(MassAssignmentException::class);

test('only one record can be the owner', function (string $model): void {
    ownerOf($model);
    ownerOf($model);
})->with('owner models')->throws(UniqueConstraintViolationException::class);

test('the owner is cached', function (string $model): void {
    $owner = ownerOf($model);
    $model::owner();

    $model::query()->whereKey($owner->id)->update(['name' => 'Changed']);

    expect($model::owner()->name)->toBe($owner->name);
})->with('owner models');

test('the owner stays the owner when its code changes', function (string $model): void {
    $owner = ownerOf($model);
    $model::owner();

    $owner->update(['code' => 'RENAMED']);

    expect($model::owner()->code)->toBe('RENAMED');
})->with('owner models');

test('saving a record forgets the cached owner', function (string $model): void {
    $owner = ownerOf($model);
    $model::owner();

    $owner->update(['name' => 'Changed']);

    expect($model::owner()->name)->toBe('Changed');
})->with('owner models');

test('the owner cannot be deleted', function (string $model): void {
    $owner = ownerOf($model);

    expect(fn (): ?bool => $owner->delete())->toThrow(LogicException::class)
        ->and($model::query()->whereKey($owner->id)->exists())->toBeTrue();
})->with('owner models');

test('deleting and restoring a record that is not the owner still works', function (string $model): void {
    $record = $model::factory()->create();

    $record->delete();
    $record->restore();

    expect($record->fresh()?->trashed())->toBeFalse();
})->with('owner models');
