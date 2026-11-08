<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Organization\Department\Show;
use App\Livewire\Pages\Internal\Controls\Organization\Department\Tabs;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view', 'internal.controls.organizations.update');
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create(['name' => 'Acme']);
    $this->department = Department::factory()->for($this->organization)->create(['code' => 'FIN', 'name' => 'Finance']);
});

test('viewing a department needs organization view', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.view');

    $this->get(route('internal.controls.organizations.departments.show', [$this->organization, $this->department]))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.organizations.view');

    $this->get(route('internal.controls.organizations.departments.show', [$this->organization, $this->department]))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeLivewire(Tabs::class)
        ->assertSee('Acme')
        ->assertSee('<title>Finance - '.config('app.name').'</title>', false);
});

test('a department of another organization is not found', function (): void {
    $other = Organization::factory()->create();

    $this->get(route('internal.controls.organizations.departments.show', [$other, $this->department]))->assertNotFound();
});

test('the browser cannot change the department of the page', function (): void {
    Livewire::test(Show::class, ['organization' => $this->organization, 'department' => $this->department])
        ->set('department', Department::factory()->for($this->organization)->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('saving changes the code and name', function (): void {
    Livewire::test(Show::class, ['organization' => $this->organization, 'department' => $this->department])
        ->assertSet('form.code', 'FIN')
        ->set('form.code', 'FN')
        ->set('form.name', 'Finance Unit')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Department updated.');

    expect($this->department->fresh()->only('code', 'name'))->toBe(['code' => 'FN', 'name' => 'Finance Unit']);
});

test('the form is read-only without organization update', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(Show::class, ['organization' => $this->organization, 'department' => $this->department])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($this->department->fresh()->name)->toBe('Finance');
});

test('the owner department is read-only, also with update', function (): void {
    $owner = Department::owner();

    Livewire::test(Show::class, ['organization' => Organization::owner(), 'department' => $owner])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($owner->fresh()->name)->toBe(config('system.owner.department.name'));
});

test('a department leads back to the departments tab of its organization', function (): void {
    $tab = route('internal.controls.organizations.show', ['organization' => $this->organization, 'tab' => 'departments']);

    $html = $this->get(route('internal.controls.organizations.departments.show', [$this->organization, $this->department]))
        ->assertOk()
        ->assertSeeInOrder([route('internal.controls.organizations.index'), route('internal.controls.organizations.show', $this->organization), $tab, 'Finance'], false)
        ->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
});
