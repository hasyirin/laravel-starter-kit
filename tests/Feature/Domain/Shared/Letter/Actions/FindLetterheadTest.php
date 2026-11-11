<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Actions\FindLetterhead;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Support\Placement;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->for($this->organization)->create();
    $this->department = Department::factory()->for($this->organization)->create();
    $this->placement = new Placement($this->organization->id, $this->branch->id, $this->department->id);
});

function letterheadFor(Organization $organization, ?Branch $branch = null, ?Department $department = null): Letterhead
{
    return Letterhead::factory()->for($organization)->create(['branch_id' => $branch?->id, 'department_id' => $department?->id]);
}

test('the letterhead for the branch and department wins', function (): void {
    letterheadFor($this->organization);
    letterheadFor($this->organization, department: $this->department);
    letterheadFor($this->organization, $this->branch);
    $exact = letterheadFor($this->organization, $this->branch, $this->department);

    expect(FindLetterhead::make()->execute($this->placement)?->is($exact))->toBeTrue();
});

test('the branch letterhead wins over the department letterhead, and the department letterhead over the organization letterhead', function (): void {
    $organizationWide = letterheadFor($this->organization);
    $forDepartment = letterheadFor($this->organization, department: $this->department);
    $forBranch = letterheadFor($this->organization, $this->branch);

    expect(FindLetterhead::make()->execute($this->placement)?->is($forBranch))->toBeTrue();

    $forBranch->delete();

    expect(FindLetterhead::make()->execute($this->placement)?->is($forDepartment))->toBeTrue();

    $forDepartment->delete();

    expect(FindLetterhead::make()->execute($this->placement)?->is($organizationWide))->toBeTrue();
});

test('two departments with the same name in two branches get their own letterheads', function (): void {
    $otherBranch = Branch::factory()->for($this->organization)->create();
    $finance = Department::factory()->for($this->organization)->create(['name' => 'Finance']);
    $otherFinance = Department::factory()->for($this->organization)->create(['name' => 'Finance']);
    $first = letterheadFor($this->organization, $this->branch, $finance);
    $second = letterheadFor($this->organization, $otherBranch, $otherFinance);

    expect(FindLetterhead::make()->execute(new Placement($this->organization->id, $this->branch->id, $finance->id))?->is($first))->toBeTrue()
        ->and(FindLetterhead::make()->execute(new Placement($this->organization->id, $otherBranch->id, $otherFinance->id))?->is($second))->toBeTrue()
        ->and(FindLetterhead::make()->execute(new Placement($this->organization->id, $otherBranch->id, $finance->id)))->toBeNull();
});

test('a placement without a branch or department matches only organization-wide letterheads', function (): void {
    letterheadFor($this->organization, $this->branch, $this->department);
    $organizationWide = letterheadFor($this->organization);

    expect(FindLetterhead::make()->execute(new Placement($this->organization->id))?->is($organizationWide))->toBeTrue();
});

test('no match gives null', function (): void {
    letterheadFor(Organization::factory()->create());
    letterheadFor($this->organization, Branch::factory()->for($this->organization)->create());
    letterheadFor($this->organization)->delete();

    expect(FindLetterhead::make()->execute($this->placement))->toBeNull()
        ->and(FindLetterhead::make()->execute(new Placement))->toBeNull();
});
