<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use Illuminate\Database\QueryException;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->department = Department::factory()->for($this->organization)->create();
});

test('a position name is unique within its organization and department', function (): void {
    Position::factory()->for($this->organization)->forDepartment($this->department)->create(['name' => 'Clerk']);

    expect(fn () => Position::factory()->for($this->organization)->forDepartment($this->department)->create(['name' => 'Clerk']))->toThrow(QueryException::class);
});

test('two positions without a department cannot share a name', function (): void {
    Position::factory()->for($this->organization)->create(['name' => 'Chief Executive Officer']);

    expect(fn () => Position::factory()->for($this->organization)->create(['name' => 'Chief Executive Officer']))->toThrow(QueryException::class);
});

test('the same name is allowed in another department or organization', function (): void {
    Position::factory()->for($this->organization)->forDepartment($this->department)->create(['name' => 'Clerk']);

    Position::factory()->for($this->organization)->forDepartment(Department::factory()->for($this->organization)->create())->create(['name' => 'Clerk']);
    Position::factory()->for($this->organization)->create(['name' => 'Clerk']);
    Position::factory()->create(['name' => 'Clerk']);

    expect(Position::query()->where('name', 'Clerk')->count())->toBe(4);
});

test('a soft-deleted position frees its name', function (): void {
    Position::factory()->for($this->organization)->create(['name' => 'Clerk'])->delete();

    Position::factory()->for($this->organization)->create(['name' => 'Clerk']);

    expect(Position::query()->where('name', 'Clerk')->count())->toBe(1);
});
