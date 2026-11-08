<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Organization\Support\PlacedUsers;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->department = Department::factory()->for($this->organization)->create();
    $this->branch = Branch::factory()->for($this->organization)->create();
    $this->position = Position::factory()->for($this->organization)->forDepartment($this->department)->create();
    $this->user = User::factory()->create();

    Profile::factory()->for($this->user)->create([
        'organization_id' => $this->organization->id,
        'department_id' => $this->department->id,
        'branch_id' => $this->branch->id,
        'position_id' => $this->position->id,
    ]);
});

test('a user is placed at the organization, branch, department, and position of their active profile', function (): void {
    foreach ([$this->organization, $this->department, $this->branch, $this->position] as $record) {
        expect(PlacedUsers::of($record)->pluck('id')->all())->toBe([$this->user->id]);
    }
});

test('an older profile does not place a user', function (): void {
    Profile::factory()->for($this->user)->create(['organization_id' => Organization::factory()->create()->id]);

    foreach ([$this->organization, $this->department, $this->branch, $this->position] as $record) {
        expect(PlacedUsers::of($record)->exists())->toBeFalse();
    }
});

test('a user holding a position of the department is placed at the department, also after the position moved there', function (): void {
    $other = Department::factory()->for($this->organization)->create();

    $this->position->update(['department_id' => $other->id]);

    expect(PlacedUsers::of($other)->pluck('id')->all())->toBe([$this->user->id]);
});
