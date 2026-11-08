<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Organization\Branch\Show;
use App\Livewire\Pages\Internal\Controls\Organization\Branch\Tabs;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view', 'internal.controls.organizations.update');
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create(['name' => 'Acme']);
    $this->branch = Branch::factory()->for($this->organization)->create(['code' => 'HQ2', 'name' => 'Headquarters']);
});

test('viewing a branch needs organization view', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.view');

    $this->get(route('internal.controls.organizations.branches.show', [$this->organization, $this->branch]))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.organizations.view');

    $this->get(route('internal.controls.organizations.branches.show', [$this->organization, $this->branch]))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeLivewire(Tabs::class)
        ->assertSee('Acme')
        ->assertSee('<title>Headquarters - '.config('app.name').'</title>', false);
});

test('a branch of another organization is not found', function (): void {
    $other = Organization::factory()->create();

    $this->get(route('internal.controls.organizations.branches.show', [$other, $this->branch]))->assertNotFound();
});

test('the browser cannot change the branch of the page', function (): void {
    Livewire::test(Show::class, ['organization' => $this->organization, 'branch' => $this->branch])
        ->set('branch', Branch::factory()->for($this->organization)->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('saving changes the code and name', function (): void {
    Livewire::test(Show::class, ['organization' => $this->organization, 'branch' => $this->branch])
        ->assertSet('form.code', 'HQ2')
        ->set('form.code', 'HQ3')
        ->set('form.name', 'Main Office')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Branch updated.');

    expect($this->branch->fresh()->only('code', 'name'))->toBe(['code' => 'HQ3', 'name' => 'Main Office']);
});

test('the form is read-only without organization update', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(Show::class, ['organization' => $this->organization, 'branch' => $this->branch])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($this->branch->fresh()->name)->toBe('Headquarters');
});

test('the owner branch is read-only, also with update', function (): void {
    $owner = Branch::owner();

    Livewire::test(Show::class, ['organization' => Organization::owner(), 'branch' => $owner])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($owner->fresh()->name)->toBe(config('system.owner.branch.name'));
});
