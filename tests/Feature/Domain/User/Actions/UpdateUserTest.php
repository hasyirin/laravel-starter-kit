<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\ConfirmPhone;
use App\Domain\User\Actions\UpdateUser;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Placement;
use App\Domain\User\Support\SmsCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->actor = User::factory()->create();
    $this->user = User::factory()->create(['password' => 'old-password']);
    $this->placement = new Placement;
});

test('changing the email clears the verification', function (): void {
    $this->user->markEmailAsVerified();

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: 'New Name',
        email: 'new@example.com',
        phone: $this->user->phone,
        password: null,
        placement: $this->placement,
    );

    $fresh = $this->user->fresh();
    expect($fresh->name)->toBe('New Name')
        ->and($fresh->email)->toBe('new@example.com')
        ->and($fresh->email_verified_at)->toBeNull();
});

test('keeping the email keeps the verification', function (): void {
    $this->user->markEmailAsVerified();

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: 'New Name',
        email: $this->user->email,
        phone: $this->user->phone,
        password: null,
        placement: $this->placement,
    );

    expect($this->user->fresh()->email_verified_at)->not->toBeNull();
});

test('a blank password keeps the old one', function (): void {
    $oldHash = $this->user->password;

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: $this->user->name,
        email: $this->user->email,
        phone: $this->user->phone,
        password: null,
        placement: $this->placement,
    );

    expect($this->user->fresh()->password)->toBe($oldHash);

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: $this->user->name,
        email: $this->user->email,
        phone: $this->user->phone,
        password: 'new-password',
        placement: $this->placement,
    );

    expect(Hash::check('new-password', $this->user->fresh()->password))->toBeTrue();
});

test('changing the placement makes a new profile and keeps the roles', function (): void {
    Role::query()->create(['name' => 'editor']);
    Profile::factory()->for($this->user)->withRoles('editor')->create();
    $department = Department::factory()->create();
    $placement = new Placement(
        organizationId: $department->organization_id,
        departmentId: $department->id,
        positionId: Position::factory()->forDepartment($department)->create()->id,
    );

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: $this->user->name,
        email: $this->user->email,
        phone: $this->user->phone,
        password: null,
        placement: $placement,
    );

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
    $placement = new Placement(organizationId: $department->organization_id, departmentId: $department->id);

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: $this->user->name,
        email: $this->user->email,
        phone: $this->user->phone,
        password: null,
        placement: $placement,
        roles: ['editor'],
        permissions: ['internal.access'],
    );

    $profile = $this->user->profile()->first();
    expect($this->user->profiles()->count())->toBe(2)
        ->and($profile?->department_id)->toBe($department->id)
        ->and($profile?->getRoleNames()->all())->toBe(['editor'])
        ->and($profile?->getPermissionNames()->all())->toBe(['internal.access']);
});

test('a user without bypass-authorization cannot change a user who has it', function (): void {
    $this->seed(PermissionSeeder::class);
    grantPermissions($this->user, 'bypass-authorization');

    expect(fn () => UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: 'New Name',
        email: 'new@example.com',
        phone: $this->user->phone,
        password: 'new-password',
        placement: $this->placement,
    ))
        ->toThrow(AuthorizationException::class);

    $fresh = $this->user->fresh();
    expect($fresh->name)->not->toBe('New Name')
        ->and($fresh->email)->not->toBe('new@example.com')
        ->and(Hash::check('old-password', $fresh->password))->toBeTrue()
        ->and($this->user->profiles()->count())->toBe(1);
});

test('changing the phone number clears the verification', function (): void {
    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: $this->user->name,
        email: $this->user->email,
        phone: '+60198765432',
        password: null,
        placement: $this->placement,
    );

    $fresh = $this->user->fresh();
    expect($fresh->phone)->toBe('+60198765432')
        ->and($fresh->phone_verified_at)->toBeNull();
});

test('keeping the phone number keeps the verification', function (): void {
    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: 'New Name',
        email: $this->user->email,
        phone: $this->user->phone,
        password: null,
        placement: $this->placement,
    );

    expect($this->user->fresh()->phone_verified_at)->not->toBeNull();
});

test('a staff number change saved after a confirm leaves the new number unverified', function (): void {
    $user = User::factory()->unverifiedPhone()->create(['phone' => '+60123456789']);
    $staff = User::query()->findOrFail($user->id);
    ConfirmPhone::make()->execute($user, SmsCode::forVerification('+60123456789')->issue());

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $staff,
        name: $staff->name,
        email: $staff->email,
        phone: '+60198765432',
        password: null,
        placement: $this->placement,
    );

    expect($user->fresh()->hasVerifiedPhone())->toBeFalse();
});
