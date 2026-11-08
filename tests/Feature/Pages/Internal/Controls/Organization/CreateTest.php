<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Organization\Create;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access');
    $this->actingAs($this->actor);
});

test('creating an organization needs create', function (): void {
    $this->get(route('internal.controls.organizations.create'))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.organizations.create');

    $this->get(route('internal.controls.organizations.create'))->assertOk()->assertSeeLivewire(Create::class);
});

test('saving creates the organization and opens its page', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.create');

    Livewire::test(Create::class)
        ->set('form.code', 'ACME')
        ->set('form.name', 'Acme')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Organization created.')
        ->assertRedirect(route('internal.controls.organizations.show', Organization::query()->where('code', 'ACME')->firstOrFail()));
});

test('the code must be unique among organizations that are not deleted', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.create');
    Organization::factory()->create(['code' => 'ACME']);
    Organization::factory()->create(['code' => 'GONE'])->delete();

    Livewire::test(Create::class)
        ->set('form.code', 'ACME')
        ->set('form.name', 'Acme')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);

    Livewire::test(Create::class)
        ->set('form.code', 'GONE')
        ->set('form.name', 'Gone again')
        ->call('save')
        ->assertHasNoErrors();
});

test('the code has at most 10 characters', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.create');

    Livewire::test(Create::class)
        ->set('form.code', str_repeat('A', 11))
        ->set('form.name', 'Acme')
        ->call('save')
        ->assertHasErrors(['form.code' => 'max']);
});
