<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\UpdateUser;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->actor = User::factory()->create();
    $this->user = User::factory()->create(['password' => 'old-password']);
    $this->placement = ['organization_id' => null, 'branch_id' => null, 'department_id' => null, 'position_id' => null];
});

test('changing the email clears the verification', function (): void {
    $this->user->markEmailAsVerified();

    UpdateUser::make()->execute($this->actor, $this->user, 'New Name', 'new@example.com', null, $this->placement);

    $fresh = $this->user->fresh();
    expect($fresh->name)->toBe('New Name')
        ->and($fresh->email)->toBe('new@example.com')
        ->and($fresh->email_verified_at)->toBeNull();
});

test('keeping the email keeps the verification', function (): void {
    $this->user->markEmailAsVerified();

    UpdateUser::make()->execute($this->actor, $this->user, 'New Name', $this->user->email, null, $this->placement);

    expect($this->user->fresh()->email_verified_at)->not->toBeNull();
});

test('a blank password keeps the old one', function (): void {
    $oldHash = $this->user->password;

    UpdateUser::make()->execute($this->actor, $this->user, $this->user->name, $this->user->email, null, $this->placement);

    expect($this->user->fresh()->password)->toBe($oldHash);

    UpdateUser::make()->execute($this->actor, $this->user, $this->user->name, $this->user->email, 'new-password', $this->placement);

    expect(Hash::check('new-password', $this->user->fresh()->password))->toBeTrue();
});

test('changing the placement makes a new profile and keeps the roles', function (): void {
    Role::query()->create(['name' => 'editor']);
    Profile::factory()->for($this->user)->withRoles('editor')->create();
    $department = Department::factory()->create();
    $placement = [
        'organization_id' => $department->organization_id,
        'branch_id' => null,
        'department_id' => $department->id,
        'position_id' => Position::factory()->forDepartment($department)->create()->id,
    ];

    UpdateUser::make()->execute($this->actor, $this->user, $this->user->name, $this->user->email, null, $placement);

    $profile = $this->user->profile()->first();
    expect($this->user->profiles()->count())->toBe(2)
        ->and($profile?->department_id)->toBe($department->id)
        ->and($profile?->getRoleNames()->all())->toBe(['editor']);
});

test('the placement, the roles, and the permissions change in one new profile', function (): void {
    $this->seed(PermissionSeeder::class);
    Role::query()->create(['name' => 'editor']);
    Profile::factory()->for($this->user)->create();
    $department = Department::factory()->create();
    $placement = ['organization_id' => $department->organization_id, 'branch_id' => null, 'department_id' => $department->id, 'position_id' => null];

    UpdateUser::make()->execute($this->actor, $this->user, $this->user->name, $this->user->email, null, $placement, ['editor'], ['internal.access']);

    $profile = $this->user->profile()->first();
    expect($this->user->profiles()->count())->toBe(2)
        ->and($profile?->department_id)->toBe($department->id)
        ->and($profile?->getRoleNames()->all())->toBe(['editor'])
        ->and($profile?->getPermissionNames()->all())->toBe(['internal.access']);
});

test('a user without bypass-authorization cannot change a user who has it', function (): void {
    $this->seed(PermissionSeeder::class);
    $this->user->givePermissionTo('bypass-authorization');

    expect(fn () => UpdateUser::make()->execute($this->actor, $this->user, 'New Name', 'new@example.com', 'new-password', $this->placement))
        ->toThrow(AuthorizationException::class);

    $fresh = $this->user->fresh();
    expect($fresh->name)->not->toBe('New Name')
        ->and($fresh->email)->not->toBe('new@example.com')
        ->and(Hash::check('old-password', $fresh->password))->toBeTrue()
        ->and($this->user->profiles()->count())->toBe(0);
});
