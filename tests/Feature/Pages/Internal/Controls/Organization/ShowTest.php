<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Organization\Show;
use App\Livewire\Pages\Internal\Controls\Organization\Tabs;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view', 'internal.controls.organizations.update');
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create(['code' => 'ACME', 'name' => 'Acme']);
});

test('viewing an organization needs view', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.view');

    $this->get(route('internal.controls.organizations.show', $this->organization))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.organizations.view');

    $this->get(route('internal.controls.organizations.show', $this->organization))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeLivewire(Tabs::class)
        ->assertSee('<title>Acme - '.config('app.name').'</title>', false);
});

test('the browser cannot change the organization of the page', function (): void {
    Livewire::test(Show::class, ['organization' => $this->organization])->set('organization', Organization::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the browser cannot change the organization of the form', function (): void {
    Livewire::test(Show::class, ['organization' => $this->organization])->set('form.organization', Organization::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('saving changes the code and name', function (): void {
    Livewire::test(Show::class, ['organization' => $this->organization])
        ->assertSet('form.code', 'ACME')
        ->set('form.code', 'ACM')
        ->set('form.name', 'Acme Sdn Bhd')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Organization updated.');

    expect($this->organization->fresh()->only('code', 'name'))->toBe(['code' => 'ACM', 'name' => 'Acme Sdn Bhd']);
});

test('saving keeps the code when it is unchanged', function (): void {
    Livewire::test(Show::class, ['organization' => $this->organization])
        ->set('form.name', 'Acme Sdn Bhd')
        ->call('save')
        ->assertHasNoErrors();
});

test('the form is read-only without update', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(Show::class, ['organization' => $this->organization])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($this->organization->fresh()->name)->toBe('Acme');
});

test('the owner organization is read-only, also with update', function (): void {
    $owner = Organization::owner();

    Livewire::test(Show::class, ['organization' => $owner])
        ->assertDontSeeHtml('type="submit"')
        ->assertSee(__('organization/organizations.owner'))
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($owner->fresh()->name)->toBe(config('system.owner.organization.name'));
});
