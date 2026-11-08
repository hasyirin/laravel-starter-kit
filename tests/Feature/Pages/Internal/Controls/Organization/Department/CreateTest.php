<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Organization\Department\Create;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view');
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create();
});

test('adding a department needs organization update', function (): void {
    $this->get(route('internal.controls.organizations.departments.create', $this->organization))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.organizations.update');

    $this->get(route('internal.controls.organizations.departments.create', $this->organization))->assertOk()->assertSeeLivewire(Create::class);
});

test('saving creates the department and opens its page', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(Create::class, ['organization' => $this->organization])
        ->set('form.code', 'FIN')
        ->set('form.name', 'Finance')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Department created.');

    $department = Department::query()->where('code', 'FIN')->firstOrFail();

    expect($department->organization_id)->toBe($this->organization->id);
});

test('saving opens the new department page', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');

    $component = Livewire::test(Create::class, ['organization' => $this->organization])
        ->set('form.code', 'FIN')
        ->set('form.name', 'Finance')
        ->call('save');

    $component->assertRedirect(route('internal.controls.organizations.departments.show', [$this->organization, Department::query()->where('code', 'FIN')->firstOrFail()]));
});

test('a department can be added to the owner organization', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(Create::class, ['organization' => Organization::owner()])
        ->set('form.code', 'FIN')
        ->set('form.name', 'Finance')
        ->call('save')
        ->assertHasNoErrors();

    expect(Organization::owner()->departments()->where('code', 'FIN')->exists())->toBeTrue();
});

test('the code must be unique within the organization', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');
    Department::factory()->for($this->organization)->create(['code' => 'FIN']);
    Department::factory()->create(['code' => 'HR']);

    Livewire::test(Create::class, ['organization' => $this->organization])
        ->set('form.code', 'FIN')
        ->set('form.name', 'Finance')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);

    Livewire::test(Create::class, ['organization' => $this->organization])
        ->set('form.code', 'HR')
        ->set('form.name', 'Human Resources')
        ->call('save')
        ->assertHasNoErrors();
});

test('the browser cannot change the organization of the form', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(Create::class, ['organization' => $this->organization])->set('form.organization', Organization::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the create page leads back to the departments tab of the organization', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');
    $tab = route('internal.controls.organizations.show', ['organization' => $this->organization, 'tab' => 'departments']);

    $html = $this->get(route('internal.controls.organizations.departments.create', $this->organization))
        ->assertOk()
        ->assertSeeInOrder([route('internal.controls.organizations.index'), route('internal.controls.organizations.show', $this->organization), $tab, __('organization/departments.create.title')], false)
        ->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
});
