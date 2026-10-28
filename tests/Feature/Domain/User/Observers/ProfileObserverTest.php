<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;

test('a profile can have a department, position, and branch of its organization', function (): void {
    $department = Department::factory()->create();

    $profile = Profile::factory()
        ->for($department->organization)
        ->for($department)
        ->for(Position::factory()->forDepartment($department))
        ->for(Branch::factory()->for($department->organization))
        ->create();

    $this->assertModelExists($profile);
});

test('a profile can have an organization-wide position without a department', function (): void {
    $position = Position::factory()->create();

    $profile = Profile::factory()->for($position->organization)->for($position)->create();

    $this->assertModelExists($profile);
});

test('a profile cannot have a department or branch of another organization', function (string $model, string $message): void {
    expect(fn (): Profile => Profile::factory()->for(Organization::factory())->for($model::factory())->create())->toThrow(LogicException::class, $message)
        ->and(Profile::query()->exists())->toBeFalse();
})->with([
    'department' => [Department::class, 'The department of a profile must belong to its organization.'],
    'branch' => [Branch::class, 'The branch of a profile must belong to its organization.'],
]);

test('a profile cannot have a department or branch without an organization', function (string $model, string $message): void {
    expect(fn (): Profile => Profile::factory()->for($model::factory())->create())->toThrow(LogicException::class, $message)
        ->and(Profile::query()->exists())->toBeFalse();
})->with([
    'department' => [Department::class, 'The department of a profile must belong to its organization.'],
    'branch' => [Branch::class, 'The branch of a profile must belong to its organization.'],
]);

test('a profile cannot have a position of another organization', function (): void {
    expect(fn (): Profile => Profile::factory()->for(Organization::factory())->for(Position::factory())->create())->toThrow(LogicException::class, 'The position of a profile must belong to its organization and department.')
        ->and(Profile::query()->exists())->toBeFalse();
});

test('a profile cannot have a position of another department', function (): void {
    $position = Position::factory()->forDepartment(Department::factory()->create())->create();

    expect(fn (): Profile => Profile::factory()->for($position->organization)->for(Department::factory()->for($position->organization))->for($position)->create())->toThrow(LogicException::class, 'The position of a profile must belong to its organization and department.')
        ->and(Profile::query()->exists())->toBeFalse();
});

test('a profile cannot have a department with an organization-wide position', function (): void {
    $position = Position::factory()->create();

    expect(fn (): Profile => Profile::factory()->for($position->organization)->for(Department::factory()->for($position->organization))->for($position)->create())->toThrow(LogicException::class, 'The position of a profile must belong to its organization and department.')
        ->and(Profile::query()->exists())->toBeFalse();
});

test('a profile cannot have a department position without that department', function (): void {
    $position = Position::factory()->forDepartment(Department::factory()->create())->create();

    expect(fn (): Profile => Profile::factory()->for($position->organization)->for($position)->create())->toThrow(LogicException::class, 'The position of a profile must belong to its organization and department.')
        ->and(Profile::query()->exists())->toBeFalse();
});

test('a profile cannot be updated', function (): void {
    $profile = Profile::factory()->create();

    expect(fn (): bool => $profile->update(['organization_id' => Organization::factory()->create()->id]))->toThrow(LogicException::class, 'Profiles cannot be updated. Create a new profile instead.')
        ->and($profile->fresh()?->organization_id)->toBeNull();
});

test('a new profile cannot have a deleted department, branch, or position', function (string $deleted): void {
    $department = Department::factory()->create();
    $position = Position::factory()->forDepartment($department)->create();
    $branch = Branch::factory()->for($department->organization)->create();
    ['department' => $department, 'position' => $position, 'branch' => $branch][$deleted]->delete();

    expect(fn () => Profile::factory()->for($department->organization)->for($department)->for($position)->for($branch)->create())->toThrow(LogicException::class);
})->with(['department', 'position', 'branch']);
