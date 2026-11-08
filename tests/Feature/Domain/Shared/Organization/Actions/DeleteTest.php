<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Actions\DeleteBranch;
use App\Domain\Shared\Organization\Actions\DeleteDepartment;
use App\Domain\Shared\Organization\Actions\DeleteOrganization;
use App\Domain\Shared\Organization\Actions\DeletePosition;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->department = Department::factory()->for($this->organization)->create();
    $this->branch = Branch::factory()->for($this->organization)->create();
    $this->position = Position::factory()->for($this->organization)->forDepartment($this->department)->create();
});

test('deleting an organization soft-deletes its branches, departments, and positions', function (): void {
    DeleteOrganization::make()->execute($this->organization);

    foreach ([$this->organization, $this->department, $this->branch, $this->position] as $record) {
        expect($record->fresh()->trashed())->toBeTrue();
    }
});

test('deleting a department soft-deletes its positions', function (): void {
    $other = Position::factory()->for($this->organization)->create();

    DeleteDepartment::make()->execute($this->department);

    expect($this->department->fresh()->trashed())->toBeTrue()
        ->and($this->position->fresh()->trashed())->toBeTrue()
        ->and($other->fresh()->trashed())->toBeFalse();
});

test('deleting a branch or position soft-deletes only that record', function (): void {
    DeleteBranch::make()->execute($this->branch);
    DeletePosition::make()->execute($this->position);

    expect($this->branch->fresh()->trashed())->toBeTrue()
        ->and($this->position->fresh()->trashed())->toBeTrue()
        ->and($this->organization->fresh()->trashed())->toBeFalse()
        ->and($this->department->fresh()->trashed())->toBeFalse();
});

test('a record with placed users cannot be deleted', function (string $record, string $action): void {
    Profile::factory()->for(User::factory())->create([
        'organization_id' => $this->organization->id,
        'department_id' => $this->department->id,
        'branch_id' => $this->branch->id,
        'position_id' => $this->position->id,
    ]);

    expect(fn () => $action::make()->execute($this->{$record}))->toThrow(LogicException::class)
        ->and($this->{$record}->fresh()->trashed())->toBeFalse();
})->with([
    ['organization', DeleteOrganization::class],
    ['department', DeleteDepartment::class],
    ['branch', DeleteBranch::class],
    ['position', DeletePosition::class],
]);

test('deleting a department is blocked by a user holding one of its positions after the position moved there', function (): void {
    $other = Department::factory()->for($this->organization)->create();
    Profile::factory()->for(User::factory())->create([
        'organization_id' => $this->organization->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);
    $this->position->update(['department_id' => $other->id]);

    expect(fn () => DeleteDepartment::make()->execute($other))->toThrow(LogicException::class)
        ->and($other->fresh()->trashed())->toBeFalse();
});
