<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Position;

test('a position can have a department of the same organization', function (): void {
    $department = Department::factory()->create();

    $position = Position::factory()->forDepartment($department)->create();

    expect($position->department_id)->toBe($department->id);
});

test('a position can have no department', function (): void {
    expect(Position::factory()->create()->department_id)->toBeNull();
});

test('a position cannot be created with a department of another organization', function (): void {
    Position::factory()->create(['department_id' => Department::factory()->create()->id]);
})->throws(LogicException::class, 'The department of a position must belong to the same organization.');

test('a position cannot move to a department of another organization', function (): void {
    $position = Position::factory()->forDepartment(Department::factory()->create())->create();
    $departmentId = $position->department_id;

    expect(fn (): bool => $position->update(['department_id' => Department::factory()->create()->id]))->toThrow(LogicException::class)
        ->and($position->fresh()?->department_id)->toBe($departmentId);
});

test('the department of a position cannot be changed after creation', function (bool $toAnother): void {
    $position = Position::factory()->forDepartment(Department::factory()->create())->create();
    $departmentId = $position->department_id;
    $target = Department::factory()->create(['organization_id' => $position->organization_id]);

    expect(fn (): bool => $position->update(['department_id' => $toAnother ? $target->id : null]))->toThrow(LogicException::class, 'The department of a position cannot be changed.')
        ->and($position->fresh()?->department_id)->toBe($departmentId);
})->with([
    'to another department' => [true],
    'to none' => [false],
]);

test('a position without a department cannot get one', function (): void {
    $position = Position::factory()->create();
    $department = Department::factory()->create(['organization_id' => $position->organization_id]);

    expect(fn (): bool => $position->update(['department_id' => $department->id]))->toThrow(LogicException::class, 'The department of a position cannot be changed.')
        ->and($position->fresh()?->department_id)->toBeNull();
});

test('a position accepts the ids of its organization and department as strings', function (): void {
    $department = Department::factory()->create();

    $position = Position::factory()->create(['organization_id' => (string) $department->organization_id, 'department_id' => (string) $department->id]);

    $this->assertModelExists($position);
});
