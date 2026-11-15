<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Actions\SaveDepartment;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\AbilityCheckFormHost;
use Tests\Fixtures\Livewire\OrganizationDepartmentFormHost;
use Tests\Fixtures\Livewire\RenamedFormHost;

test('the ability helpers check the permissions of the current user', function (array $permissions, array $results): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, ...$permissions);
    $this->actingAs($user);

    $component = Livewire::test(AbilityCheckFormHost::class);

    $component->assertSet('form.results', $results);
})->with([
    'with system access' => [['system.access'], ['can' => true, 'cannot' => false, 'canAny' => true, 'cannotAny' => false]],
    'with Horizon access only' => [['system.horizon.view'], ['can' => false, 'cannot' => true, 'canAny' => true, 'cannotAny' => false]],
    'without permissions' => [[], ['can' => false, 'cannot' => true, 'canAny' => false, 'cannotAny' => true]],
]);

test('authorize checks the permissions of the current user', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'system.horizon.view');
    $this->actingAs($user);

    $form = Livewire::test(AbilityCheckFormHost::class)->instance()->form;

    expect($form->authorize('system.horizon.view')->allowed())->toBeTrue()
        ->and(fn () => $form->authorize('system.access'))->toThrow(AuthorizationException::class);
});

test('a form that calls two Action classes in a transaction saves both', function (): void {
    $organization = Organization::factory()->create(['name' => 'Old name']);

    Livewire::test(OrganizationDepartmentFormHost::class, ['organization' => $organization])
        ->set('form.name', 'New name')
        ->set('form.department_code', 'FIN')
        ->set('form.department_name', 'Finance')
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->fresh()?->name)->toBe('New name')
        ->and(Department::query()->whereBelongsTo($organization)->pluck('code')->all())->toBe(['FIN']);
});

test('a failed second Action class rolls back the first and shows its error on the form', function (): void {
    $organization = Organization::factory()->create(['name' => 'Old name']);
    $this->mock(SaveDepartment::class)->expects('execute')->andThrow(ValidationException::withMessages(['code' => 'The code is taken.']));

    $component = Livewire::test(OrganizationDepartmentFormHost::class, ['organization' => $organization])
        ->set('form.name', 'New name')
        ->set('form.department_code', 'FIN')
        ->set('form.department_name', 'Finance')
        ->call('save')
        ->assertHasErrors(['form.department_code' => 'The code is taken.'])
        ->assertSee('Old name')
        ->assertDontSee('New name');

    expect($organization->fresh()?->name)->toBe('Old name')
        ->and($component->instance()->form->organization->name)->toBe('Old name')
        ->and($component->instance()->form->organization->isDirty())->toBeFalse()
        ->and($component->get('form.name'))->toBe('New name')
        ->and(Department::query()->whereBelongsTo($organization)->exists())->toBeFalse();
});

test('an Action error shows on the field of a form under any property name', function (): void {
    $organization = Organization::factory()->create();
    $this->mock(SaveDepartment::class)->expects('execute')->andThrow(ValidationException::withMessages(['code' => 'The code is taken.']));

    Livewire::test(RenamedFormHost::class, ['organization' => $organization])
        ->set('departmentForm.name', 'New name')
        ->set('departmentForm.department_code', 'FIN')
        ->set('departmentForm.department_name', 'Finance')
        ->call('save')
        ->assertHasErrors(['departmentForm.department_code' => 'The code is taken.'])
        ->assertHasNoErrors(['form.department_code'])
        ->assertSee('The code is taken.');
});
