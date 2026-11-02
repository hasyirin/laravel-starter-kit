<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Admin\Controls\User\Show;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(['admin.access', 'admin.controls.users.view', 'admin.controls.users.update']);
    $this->actingAs($this->admin);
    $this->subject = User::factory()->create(['email_verified_at' => null]);
});

test('viewing a user needs view', function (): void {
    $this->admin->revokePermissionTo('admin.controls.users.view');

    $this->get(route('admin.controls.users.show', $this->subject))->assertForbidden();

    $this->admin->givePermissionTo('admin.controls.users.view');

    $this->get(route('admin.controls.users.show', $this->subject))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSee($this->subject->name);
});

test('the form shows the current placement', function (): void {
    $department = Department::factory()->create();
    Profile::factory()->for($this->subject)->create([
        'organization_id' => $department->organization_id,
        'department_id' => $department->id,
    ]);

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertSet('form.name', $this->subject->name)
        ->assertSet('form.email', $this->subject->email)
        ->assertSet('form.organization_id', $department->organization_id)
        ->assertSet('form.department_id', $department->id)
        ->assertSet('form.branch_id', null);
});

test('saving keeps the roles of the profile', function (): void {
    $role = Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    Profile::factory()->for($this->subject)->withRoles($role)->create();
    $department = Department::factory()->create();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.name', 'Renamed')
        ->set('form.organization_id', $department->organization_id)
        ->set('form.department_id', $department->id)
        ->call('save')
        ->assertHasNoErrors();

    $profile = $this->subject->fresh()->profile;
    expect($this->subject->fresh()->name)->toBe('Renamed')
        ->and($profile->department_id)->toBe($department->id)
        ->and($profile->getRoleNames()->all())->toBe(['editor']);
});

test('a blank password keeps the old one', function (): void {
    $hash = $this->subject->password;

    Livewire::test(Show::class, ['user' => $this->subject])
        ->set('form.name', 'Renamed')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->subject->fresh()->password)->toBe($hash);
});

test('the form is read-only without update', function (): void {
    $this->admin->revokePermissionTo('admin.controls.users.update');

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertDontSee(__('common/buttons.save'))
        ->assertSeeHtml('wire:model="form.name"')
        ->set('form.name', 'Renamed')
        ->call('save')
        ->assertForbidden();

    expect($this->subject->fresh()->name)->not->toBe('Renamed');
});

test('the form is editable with update', function (): void {
    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertSee(__('common/buttons.save'));
});

test('an unverified email can be marked as verified', function (): void {
    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertSee(__('user/users.unverified'))
        ->call('verifyEmail')
        ->assertDontSee(__('user/users.unverified'));

    expect($this->subject->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('marking an email as verified needs update', function (): void {
    $this->admin->revokePermissionTo('admin.controls.users.update');

    Livewire::test(Show::class, ['user' => $this->subject])
        ->call('verifyEmail')
        ->assertForbidden();

    expect($this->subject->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the verify button is hidden once verified', function (): void {
    $this->subject->markEmailAsVerified();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertDontSee(__('user/users.unverified'))
        ->assertDontSee(__('user/users.mark_as_verified'));
});

test('a user without a profile opens', function (): void {
    expect($this->subject->profile)->toBeNull();

    Livewire::test(Show::class, ['user' => $this->subject])
        ->assertOk()
        ->assertSet('form.organization_id', null);
});
