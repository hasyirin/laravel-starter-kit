<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;

test('a letterhead can have a branch and a department of its organization', function (): void {
    $organization = Organization::factory()->create();
    $branch = Branch::factory()->for($organization)->create();
    $department = Department::factory()->for($organization)->create();

    $letterhead = Letterhead::factory()->for($organization)->create(['branch_id' => $branch->id, 'department_id' => $department->id]);

    expect($letterhead->branch_id)->toBe($branch->id)
        ->and($letterhead->department_id)->toBe($department->id);
});

test('a letterhead cannot have a branch of another organization', function (): void {
    Letterhead::factory()->create(['branch_id' => Branch::factory()->create()->id]);
})->throws(LogicException::class, 'The branch of a letterhead must belong to its organization.');

test('a letterhead cannot have a department of another organization', function (): void {
    Letterhead::factory()->create(['department_id' => Department::factory()->create()->id]);
})->throws(LogicException::class, 'The department of a letterhead must belong to its organization.');

test('a letterhead cannot move to a branch of another organization', function (): void {
    $letterhead = Letterhead::factory()->create();

    expect(fn (): bool => $letterhead->update(['branch_id' => Branch::factory()->create()->id]))->toThrow(LogicException::class)
        ->and($letterhead->fresh()?->branch_id)->toBeNull();
});
