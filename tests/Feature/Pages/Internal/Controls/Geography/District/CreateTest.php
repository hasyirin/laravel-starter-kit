<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\District\Create;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.create');
    $this->actingAs($this->actor);
    $this->state = State::factory()->create();
    $this->country = $this->state->country;
});

test('adding a district needs geography create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    $this->get(route('internal.controls.geography.countries.states.districts.create', [$this->country, $this->state]))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.create');

    $this->get(route('internal.controls.geography.countries.states.districts.create', [$this->country, $this->state]))->assertOk()->assertSeeLivewire(Create::class);
});

test('saving creates the district in the state and opens its page', function (): void {
    $component = Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.code', '0101')
        ->set('form.name', 'Batu Pahat')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'District created.');

    $district = District::query()->where('code', '0101')->firstOrFail();

    expect($district->state_id)->toBe($this->state->id);
    $component->assertRedirect(route('internal.controls.geography.countries.states.districts.show', [$this->country, $this->state, $district]));
});

test('the code must be unique within the state', function (): void {
    District::factory()->for($this->state)->create(['code' => '0101']);

    Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.code', '0101')
        ->set('form.name', 'Batu Pahat')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('the code of a deleted district restores that district', function (): void {
    $deleted = District::factory()->for($this->state)->create(['code' => '0101']);
    $deleted->delete();

    Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.code', '0101')
        ->set('form.name', 'Batu Pahat')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('internal.controls.geography.countries.states.districts.show', [$this->country, $this->state, $deleted]));

    expect($deleted->fresh()->trashed())->toBeFalse();
});

test('the create page leads back to the districts tab of the state', function (): void {
    $tab = route('internal.controls.geography.countries.states.show', ['country' => $this->country, 'state' => $this->state, 'tab' => 'districts']);

    $html = $this->get(route('internal.controls.geography.countries.states.districts.create', [$this->country, $this->state]))
        ->assertOk()
        ->assertSeeInOrder([route('internal.controls.geography.countries.states.show', [$this->country, $this->state]), $tab, __('geography/districts.create.title')], false)
        ->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
});

test('a code with outer spaces is taken by the same code', function (): void {
    District::factory()->for($this->state)->create(['code' => '0101']);

    Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.code', ' 0101 ')
        ->set('form.name', 'Batu Pahat')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('saving trims the code and the name and collapses their inner spaces', function (): void {
    Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.code', ' 0101 ')
        ->set('form.name', '  Batu   Pahat ')
        ->call('save')
        ->assertHasNoErrors();

    expect(District::query()->where('code', '0101')->firstOrFail()->name)->toBe('Batu Pahat');
});
