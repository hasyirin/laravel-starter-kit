<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Organization\PositionModal;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view', 'internal.controls.organizations.update');
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create();
    $this->department = Department::factory()->for($this->organization)->create();
});

test('adding a position needs organization update', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(PositionModal::class, ['organization' => $this->organization])
        ->call('show')
        ->assertForbidden();
});

test('saving adds a position to the organization', function (): void {
    Livewire::test(PositionModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Clerk')
        ->set('form.department_id', $this->department->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched(PositionModal::event())
        ->assertSet('showing', false);

    $position = Position::query()->where('name', 'Clerk')->firstOrFail();

    expect($position->organization_id)->toBe($this->organization->id)
        ->and($position->department_id)->toBe($this->department->id);
});

test('a position added from a department tab starts in that department', function (): void {
    Livewire::test(PositionModal::class, ['organization' => $this->organization, 'department' => $this->department])
        ->call('show')
        ->assertSet('form.department_id', $this->department->id);
});

test('a position can move to another department of the organization or to none', function (): void {
    $position = Position::factory()->for($this->organization)->forDepartment($this->department)->create();
    $other = Department::factory()->for($this->organization)->create();

    Livewire::test(PositionModal::class, ['organization' => $this->organization])
        ->call('show', $position->id)
        ->assertSet('form.department_id', $this->department->id)
        ->set('form.department_id', $other->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($position->fresh()->department_id)->toBe($other->id);

    Livewire::test(PositionModal::class, ['organization' => $this->organization])
        ->call('show', $position->id)
        ->set('form.department_id')
        ->call('save')
        ->assertHasNoErrors();

    expect($position->fresh()->department_id)->toBeNull();
});

test('a department of another organization is refused', function (): void {
    Livewire::test(PositionModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Clerk')
        ->set('form.department_id', Department::factory()->create()->id)
        ->call('save')
        ->assertHasErrors(['form.department_id' => 'exists']);
});

test('a position of another organization is not found', function (): void {
    $position = Position::factory()->create();

    Livewire::test(PositionModal::class, ['organization' => $this->organization])
        ->call('show', $position->id)
        ->assertNotFound();
});

test('a position name is unique within its department', function (): void {
    Position::factory()->for($this->organization)->forDepartment($this->department)->create(['name' => 'Clerk']);

    Livewire::test(PositionModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Clerk')
        ->set('form.department_id', $this->department->id)
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique']);
});

test('a position name is unique among positions without a department', function (): void {
    Position::factory()->for($this->organization)->create(['name' => 'Chief Executive Officer']);

    Livewire::test(PositionModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Chief Executive Officer')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique']);
});

test('a position keeps its own name on save', function (): void {
    $position = Position::factory()->for($this->organization)->create(['name' => 'Clerk']);

    Livewire::test(PositionModal::class, ['organization' => $this->organization])
        ->call('show', $position->id)
        ->call('save')
        ->assertHasNoErrors();
});
