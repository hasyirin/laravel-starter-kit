<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Admin\Controls\User\Create;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('admin.access');
    $this->actingAs($this->admin);
});

test('creating a user needs create', function (): void {
    $this->get(route('admin.controls.users.create'))->assertForbidden();

    $this->admin->givePermissionTo('admin.controls.users.create');

    $this->get(route('admin.controls.users.create'))->assertOk()->assertSeeLivewire(Create::class);
});

test('a new user opens on their page', function (): void {
    $this->admin->givePermissionTo('admin.controls.users.create');
    $organization = Organization::factory()->create();
    $department = Department::factory()->create(['organization_id' => $organization->id]);
    $position = Position::factory()->forDepartment($department)->create();

    Livewire::test(Create::class)
        ->set('form.name', 'New Person')
        ->set('form.email', 'new.person@example.com')
        ->set('form.password', 'Str0ng-Passw0rd!')
        ->set('form.password_confirmation', 'Str0ng-Passw0rd!')
        ->set('form.organization_id', $organization->id)
        ->set('form.department_id', $department->id)
        ->set('form.position_id', $position->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.controls.users.show', User::query()->where('email', 'new.person@example.com')->firstOrFail()));

    $profile = User::query()->where('email', 'new.person@example.com')->firstOrFail()->profile;
    expect($profile->organization_id)->toBe($organization->id)
        ->and($profile->department_id)->toBe($department->id)
        ->and($profile->position_id)->toBe($position->id);
});

test('a department of another organization is refused', function (): void {
    $this->admin->givePermissionTo('admin.controls.users.create');
    $organization = Organization::factory()->create();
    $foreignDepartment = Department::factory()->create();

    Livewire::test(Create::class)
        ->set('form.name', 'New Person')
        ->set('form.email', 'new.person@example.com')
        ->set('form.password', 'Str0ng-Passw0rd!')
        ->set('form.password_confirmation', 'Str0ng-Passw0rd!')
        ->set('form.organization_id', $organization->id)
        ->set('form.department_id', $foreignDepartment->id)
        ->call('save')
        ->assertHasErrors('form.department_id');

    expect(User::query()->where('email', 'new.person@example.com')->exists())->toBeFalse();
});

test('changing the organization clears the placement that no longer fits', function (): void {
    $this->admin->givePermissionTo('admin.controls.users.create');
    $department = Department::factory()->create();
    $position = Position::factory()->forDepartment($department)->create();
    $other = Organization::factory()->create();

    Livewire::test(Create::class)
        ->set('form.organization_id', $department->organization_id)
        ->set('form.department_id', $department->id)
        ->set('form.position_id', $position->id)
        ->set('form.organization_id', $other->id)
        ->assertSet('form.department_id', null)
        ->assertSet('form.position_id', null);
});

test('clearing the organization empties the placement', function (): void {
    $this->admin->givePermissionTo('admin.controls.users.create');
    $department = Department::factory()->create();

    Livewire::test(Create::class)
        ->set('form.organization_id', $department->organization_id)
        ->set('form.department_id', $department->id)
        ->set('form.organization_id', '')
        ->assertSet('form.organization_id', null)
        ->assertSet('form.department_id', null);
});

test('the password is required on create', function (): void {
    $this->admin->givePermissionTo('admin.controls.users.create');

    Livewire::test(Create::class)
        ->set('form.name', 'New Person')
        ->set('form.email', 'new.person@example.com')
        ->call('save')
        ->assertHasErrors('form.password');
});
