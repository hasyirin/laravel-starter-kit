<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\Management\UpdateUser;
use App\Domain\User\Actions\Security\ConfirmPhone;
use App\Domain\User\Actions\Session\EndAllSessions;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use App\Domain\User\Support\Impersonation;
use App\Domain\User\Support\Placement;
use App\Domain\User\Support\SmsCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
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

test('an actor who changes their own password through a separate instance holds the new password hash', function (): void {
    $sameUser = User::query()->findOrFail($this->actor->id);

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $sameUser,
        name: $sameUser->name,
        email: $sameUser->email,
        phone: $sameUser->phone,
        password: 'new-password',
        placement: $this->placement,
    );

    expect(Hash::check('new-password', $this->actor->password))->toBeTrue();
});

test('an actor who changes their own password through a separate instance keeps the old hash when an outer transaction rolls back', function (): void {
    $oldHash = $this->actor->password;
    $sameUser = User::query()->findOrFail($this->actor->id);

    expect(fn (): mixed => DB::transaction(function () use ($sameUser): void {
        UpdateUser::make()->execute(
            actor: $this->actor,
            user: $sameUser,
            name: $sameUser->name,
            email: $sameUser->email,
            phone: $sameUser->phone,
            password: 'new-password',
            placement: $this->placement,
        );

        throw new RuntimeException('A later step failed.');
    }))->toThrow(RuntimeException::class, 'A later step failed.');

    expect($this->actor->password)->toBe($oldHash);
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

test('staff who change the password of another user end all sessions of that user, and keep the session where someone impersonates that user', function (): void {
    UserSession::factory()->count(2)->forUser($this->user)->create();
    UserSession::factory()->impersonatedBy($this->user)->create();
    $impersonatorSession = UserSession::factory()->forUser($this->user)->impersonatedBy(User::factory()->create())->create();
    $actorSession = UserSession::factory()->forUser($this->actor)->create();
    $this->actingAs($this->actor);
    session()->put(UserSession::SESSION_KEY, $actorSession->id);

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: $this->user->name,
        email: $this->user->email,
        phone: $this->user->phone,
        password: 'new-password',
        placement: $this->placement,
    );

    expect(UserSession::query()->orderBy('id')->pluck('id')->all())->toBe([$impersonatorSession->id, $actorSession->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$this->actor->id, $this->actor->id, $this->actor->id]);
});

test('staff who change their own password end their other sessions and keep the current one', function (): void {
    $current = UserSession::factory()->forUser($this->actor)->create();
    UserSession::factory()->count(2)->forUser($this->actor)->create();
    $this->actingAs($this->actor);
    session()->put(UserSession::SESSION_KEY, $current->id);

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->actor,
        name: $this->actor->name,
        email: $this->actor->email,
        phone: $this->actor->phone,
        password: 'new-password',
        placement: $this->placement,
    );

    expect(UserSession::query()->pluck('id')->all())->toBe([$current->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$this->actor->id, $this->actor->id]);
});

test('staff who change a user without a new password keep the sessions of that user', function (): void {
    UserSession::factory()->forUser($this->user)->create();
    $this->actingAs($this->actor);

    UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: 'New Name',
        email: $this->user->email,
        phone: $this->user->phone,
        password: null,
        placement: $this->placement,
    );

    expect(UserSession::query()->count())->toBe(1)
        ->and(SignInEntry::query()->count())->toBe(0);
});

test('the impersonator is who ended the sessions when they change the password of the impersonated user', function (): void {
    $impersonator = User::factory()->create();
    UserSession::factory()->count(2)->forUser($this->user)->create();
    $current = UserSession::factory()->forUser($this->user)->impersonatedBy($impersonator)->create();
    $this->actingAs($this->user);
    Impersonation::begin($impersonator, 'https://example.test/start');
    session()->put(UserSession::SESSION_KEY, $current->id);

    UpdateUser::make()->execute(
        actor: $this->user,
        user: $this->user,
        name: $this->user->name,
        email: $this->user->email,
        phone: $this->user->phone,
        password: 'new-password',
        placement: $this->placement,
    );

    expect(UserSession::query()->pluck('id')->all())->toBe([$current->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$impersonator->id, $impersonator->id]);
});

test('an impersonator who changes their own password on the users page keeps their current session', function (): void {
    $impersonator = User::factory()->create();
    $ownSession = UserSession::factory()->forUser($impersonator)->create();
    $current = UserSession::factory()->forUser($this->user)->impersonatedBy($impersonator)->create();
    $this->actingAs($this->user);
    Impersonation::begin($impersonator, 'https://example.test/start');
    session()->put(UserSession::SESSION_KEY, $current->id);

    UpdateUser::make()->execute(
        actor: $this->user,
        user: $impersonator,
        name: $impersonator->name,
        email: $impersonator->email,
        phone: $impersonator->phone,
        password: 'new-password',
        placement: $this->placement,
    );

    expect(UserSession::query()->pluck('id')->all())->toBe([$current->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->sole()->only('user_id', 'ended_by_id'))->toBe([
            'user_id' => $ownSession->user_id,
            'ended_by_id' => $impersonator->id,
        ]);
});

test('a failure to end the sessions keeps the old password and the other values', function (): void {
    $oldHash = $this->user->password;
    $oldName = $this->user->name;
    $this->mock(EndAllSessions::class)->shouldReceive('execute')->andThrow(new RuntimeException('Ending failed.'));

    expect(fn () => UpdateUser::make()->execute(
        actor: $this->actor,
        user: $this->user,
        name: 'New Name',
        email: $this->user->email,
        phone: $this->user->phone,
        password: 'new-password',
        placement: $this->placement,
    ))->toThrow(RuntimeException::class, 'Ending failed.');

    $fresh = $this->user->fresh();
    expect($fresh->password)->toBe($oldHash)
        ->and($fresh->name)->toBe($oldName);
});
