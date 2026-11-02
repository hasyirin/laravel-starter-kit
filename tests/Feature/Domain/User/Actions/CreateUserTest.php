<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Actions\CreateUser;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

test('creating a user gives them a first profile with the placement', function (): void {
    $department = Department::factory()->create();
    $placement = [
        'organization_id' => $department->organization_id,
        'branch_id' => Branch::factory()->for($department->organization)->create()->id,
        'department_id' => $department->id,
        'position_id' => Position::factory()->forDepartment($department)->create()->id,
    ];

    $user = CreateUser::make()->execute(User::factory()->create(), 'Ada Lovelace', 'ada@example.com', 'secret-password', $placement);

    expect($user->exists)->toBeTrue()
        ->and($user->name)->toBe('Ada Lovelace')
        ->and($user->email)->toBe('ada@example.com')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->profiles()->count())->toBe(1)
        ->and($user->profile()->first()?->only(array_keys($placement)))->toBe($placement);
});
