<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Organization\Branch\Create;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view');
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create();
});

test('adding a branch needs organization update', function (): void {
    $this->get(route('internal.controls.organizations.branches.create', $this->organization))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.organizations.update');

    $this->get(route('internal.controls.organizations.branches.create', $this->organization))->assertOk()->assertSeeLivewire(Create::class);
});

test('saving creates the branch and opens its page', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(Create::class, ['organization' => $this->organization])
        ->set('form.code', 'HQ2')
        ->set('form.name', 'Headquarters')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Branch created.');

    $branch = Branch::query()->where('code', 'HQ2')->firstOrFail();

    expect($branch->organization_id)->toBe($this->organization->id);
});

test('saving opens the new branch page', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');

    $component = Livewire::test(Create::class, ['organization' => $this->organization])
        ->set('form.code', 'HQ2')
        ->set('form.name', 'Headquarters')
        ->call('save');

    $component->assertRedirect(route('internal.controls.organizations.branches.show', [$this->organization, Branch::query()->where('code', 'HQ2')->firstOrFail()]));
});

test('a branch can be added to the owner organization', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(Create::class, ['organization' => Organization::owner()])
        ->set('form.code', 'HQ2')
        ->set('form.name', 'Headquarters')
        ->call('save')
        ->assertHasNoErrors();

    expect(Organization::owner()->branches()->where('code', 'HQ2')->exists())->toBeTrue();
});

test('the code must be unique within the organization', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');
    Branch::factory()->for($this->organization)->create(['code' => 'HQ2']);
    Branch::factory()->create(['code' => 'KL']);

    Livewire::test(Create::class, ['organization' => $this->organization])
        ->set('form.code', 'HQ2')
        ->set('form.name', 'Headquarters')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);

    Livewire::test(Create::class, ['organization' => $this->organization])
        ->set('form.code', 'KL')
        ->set('form.name', 'Kuala Lumpur')
        ->call('save')
        ->assertHasNoErrors();
});

test('the browser cannot change the organization of the form', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(Create::class, ['organization' => $this->organization])->set('form.organization', Organization::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the create page leads back to the branches tab of the organization', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.update');
    $tab = route('internal.controls.organizations.show', ['organization' => $this->organization, 'tab' => 'branches']);

    $html = $this->get(route('internal.controls.organizations.branches.create', $this->organization))
        ->assertOk()
        ->assertSeeInOrder([route('internal.controls.organizations.index'), route('internal.controls.organizations.show', $this->organization), $tab, __('organization/branches.create.title')], false)
        ->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
});
