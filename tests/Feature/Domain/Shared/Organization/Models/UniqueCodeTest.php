<?php

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\UniqueConstraintViolationException;

test('two organizations cannot share a code', function (): void {
    Organization::factory()->count(2)->create(['code' => 'ORG']);
})->throws(UniqueConstraintViolationException::class);

test('a deleted organization does not block its code', function (): void {
    Organization::factory()->create(['code' => 'ORG'])->delete();

    expect(Organization::factory()->create(['code' => 'ORG'])->exists)->toBeTrue();
});

test('departments and branches cannot share a code in one organization', function (string $model): void {
    $organization = Organization::factory()->create();

    $model::factory()->for($organization)->count(2)->create(['code' => 'HQ']);
})->with([Department::class, Branch::class])->throws(UniqueConstraintViolationException::class);

test('departments and branches can share a code across organizations', function (string $model): void {
    $model::factory()->create(['code' => 'HQ']);
    $model::factory()->create(['code' => 'HQ']);

    expect($model::query()->where('code', 'HQ')->count())->toBe(2);
})->with([Department::class, Branch::class]);
