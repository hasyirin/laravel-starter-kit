<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Actions\SaveBranch;
use App\Domain\Shared\Organization\Actions\SaveDepartment;
use App\Domain\Shared\Organization\Actions\SaveOrganization;
use App\Domain\Shared\Organization\Actions\SavePosition;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;

test('saving creates and updates an organization', function (): void {
    $organization = SaveOrganization::make()->execute(new Organization, 'ACME', 'Acme');

    expect($organization->exists)->toBeTrue()
        ->and($organization->is_owner)->toBeFalse();

    SaveOrganization::make()->execute($organization, 'ACM', 'Acme Sdn Bhd');

    expect($organization->fresh()->only('code', 'name'))->toBe(['code' => 'ACM', 'name' => 'Acme Sdn Bhd']);
});

test('saving creates and updates a department or branch of the organization', function (string $action, string $model): void {
    $organization = Organization::factory()->create();

    $record = $action::make()->execute($organization, new $model, 'FIN', 'Finance');

    expect($record->organization_id)->toBe($organization->id)
        ->and($record->is_owner)->toBeFalse();

    $action::make()->execute($organization, $record, 'FN', 'Finance Unit');

    expect($record->fresh()->only('code', 'name'))->toBe(['code' => 'FN', 'name' => 'Finance Unit']);
})->with([[SaveDepartment::class, Department::class], [SaveBranch::class, Branch::class]]);

test('saving creates and updates a position of the organization', function (): void {
    $organization = Organization::factory()->create();
    $department = Department::factory()->for($organization)->create();

    $position = SavePosition::make()->execute($organization, new Position, $department, 'Clerk');

    expect($position->organization_id)->toBe($organization->id)
        ->and($position->department_id)->toBe($department->id);

    SavePosition::make()->execute($organization, $position, $department, 'Senior Clerk');

    expect($position->fresh()->name)->toBe('Senior Clerk');
});

test('a position cannot move to another department or to none', function (?string $target): void {
    $organization = Organization::factory()->create();
    $department = Department::factory()->for($organization)->create();
    $position = Position::factory()->for($organization)->forDepartment($department)->create();
    $other = $target === null ? null : Department::factory()->for($organization)->create();

    expect(fn (): Position => SavePosition::make()->execute($organization, $position, $other, $position->name))->toThrow(LogicException::class)
        ->and($position->fresh()->department_id)->toBe($department->id);
})->with([
    'to another department' => ['other'],
    'to none' => [null],
]);

test('saving the owner organization is refused', function (): void {
    expect(fn (): Organization => SaveOrganization::make()->execute(Organization::owner(), 'X', 'X'))->toThrow(LogicException::class);
});

test('saving the owner department or owner branch is refused', function (string $action, string $model): void {
    expect(fn () => $action::make()->execute(Organization::owner(), $model::owner(), 'X', 'X'))->toThrow(LogicException::class);
})->with([[SaveDepartment::class, Department::class], [SaveBranch::class, Branch::class]]);
