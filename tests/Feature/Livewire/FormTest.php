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
use Tests\Fixtures\Livewire\InnerValidationFormHost;
use Tests\Fixtures\Livewire\NestedFormHost;
use Tests\Fixtures\Livewire\OrganizationDepartmentFormHost;
use Tests\Fixtures\Livewire\RenamedFormHost;
use Tests\Fixtures\Livewire\TwoFormsHost;
use Tests\Fixtures\Validation\ContractValidator;

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

test('an error key that already has the property name of the form keeps it once', function (): void {
    Livewire::test(InnerValidationFormHost::class)
        ->call('save')
        ->assertHasErrors(['innerForm.name'])
        ->assertHasNoErrors(['innerForm.innerForm.name']);
});

test('an error key from validateOnly() keeps the property name of the form once', function (): void {
    Livewire::test(InnerValidationFormHost::class)
        ->call('saveName')
        ->assertHasErrors(['innerForm.name' => 'required'])
        ->assertHasNoErrors(['innerForm.innerForm.name']);
});

test('an Action error of a nested form shows under its full path', function (): void {
    Livewire::test(NestedFormHost::class)
        ->call('refuse')
        ->assertHasErrors(['form.inner.name' => 'The name is taken.'])
        ->assertHasNoErrors(['form.form.inner.name']);
});

test('an Action error keeps its failed rule on the field of the form', function (): void {
    $organization = Organization::factory()->create();
    $this->mock(SaveDepartment::class)->expects('execute')->andReturnUsing(fn (): array => validator(['code' => 'FIN'], ['code' => 'integer'])->validate());

    Livewire::test(OrganizationDepartmentFormHost::class, ['organization' => $organization])
        ->set('form.name', 'New name')
        ->set('form.department_code', 'FIN')
        ->set('form.department_name', 'Finance')
        ->call('save')
        ->assertHasErrors(['form.department_code' => 'integer']);
});

test('an Action error from a validator that only implements the contract shows on the field of the form', function (): void {
    $organization = Organization::factory()->create();
    $validator = new ContractValidator(['code' => ['The code is taken.']], ['code' => ['Unique' => []]]);
    $this->mock(SaveDepartment::class)->expects('execute')->andThrow(new ValidationException($validator));

    Livewire::test(OrganizationDepartmentFormHost::class, ['organization' => $organization])
        ->set('form.name', 'New name')
        ->set('form.department_code', 'FIN')
        ->set('form.department_name', 'Finance')
        ->call('save')
        ->assertHasErrors(['form.department_code' => ['The code is taken.', 'unique']]);
});

test('an Action error key that starts with the property name of the form still gets the prefix', function (): void {
    $organization = Organization::factory()->create();
    $this->mock(SaveDepartment::class)->expects('execute')->andThrow(ValidationException::withMessages(['form.code' => 'The code is taken.']));

    Livewire::test(OrganizationDepartmentFormHost::class, ['organization' => $organization])
        ->set('form.name', 'New name')
        ->set('form.department_code', 'FIN')
        ->set('form.department_name', 'Finance')
        ->call('save')
        ->assertHasErrors(['form.department_form.code' => 'The code is taken.']);
});

test('a nested form shows its errors under its full path on every request', function (): void {
    Livewire::test(NestedFormHost::class)
        ->call('save')
        ->assertHasErrors(['form.inner.name' => 'required'])
        ->assertSee(__('validation.required', ['attribute' => 'name']))
        ->call('save')
        ->assertHasErrors(['form.inner.name' => 'required']);
});

test('a failed second form rolls back the first and puts back its model', function (): void {
    $organization = Organization::factory()->create(['name' => 'Old name']);

    $component = Livewire::test(TwoFormsHost::class, ['organization' => $organization])
        ->set('form.name', 'New name')
        ->set('form.department_code', 'FIN')
        ->set('form.department_name', 'Finance')
        ->call('save')
        ->assertHasErrors(['innerForm.name' => 'required'])
        ->assertSee('Old name')
        ->assertSet('department', null);

    expect($organization->fresh()?->name)->toBe('Old name')
        ->and($component->instance()->form->organization->isDirty())->toBeFalse()
        ->and(Department::query()->whereBelongsTo($organization)->exists())->toBeFalse();

    $component->set('innerForm.name', 'Inner')
        ->call('save')
        ->assertHasNoErrors();

    expect($component->instance()->department?->code)->toBe('FIN')
        ->and($organization->fresh()?->name)->toBe('New name');
});
