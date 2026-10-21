<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;

dataset('organization children', [Department::class, Branch::class, Position::class]);

test('the organization cannot be changed after creation', function (string $model): void {
    $record = $model::factory()->create();
    $organizationId = $record->organization_id;

    expect(fn (): bool => $record->update(['organization_id' => Organization::factory()->create()->id]))->toThrow(LogicException::class)
        ->and($record->fresh()?->organization_id)->toBe($organizationId);
})->with('organization children');

test('other attributes can still be changed', function (string $model): void {
    $record = $model::factory()->create();

    $record->update(['name' => 'Changed']);

    expect($record->fresh()?->name)->toBe('Changed');
})->with('organization children');
