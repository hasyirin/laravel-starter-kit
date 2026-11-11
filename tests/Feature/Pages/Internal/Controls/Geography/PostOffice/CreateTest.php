<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\PostOffice\Create;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.create');
    $this->actingAs($this->actor);
    $this->state = State::factory()->create();
    $this->country = $this->state->country;
});

test('adding a city needs geography create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    $this->get(route('internal.controls.geography.countries.states.cities.create', [$this->country, $this->state]))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.create');

    $this->get(route('internal.controls.geography.countries.states.cities.create', [$this->country, $this->state]))->assertOk()->assertSeeLivewire(Create::class);
});

test('a city cannot be added to a state of another country', function (): void {
    $this->get(route('internal.controls.geography.countries.states.cities.create', [State::factory()->create()->country, $this->state]))->assertNotFound();
});

test('saving creates the city in the state and opens its page', function (): void {
    $component = Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.name', 'Muar')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'City created.');

    $postOffice = PostOffice::query()->where('name', 'Muar')->firstOrFail();

    expect($postOffice->state_id)->toBe($this->state->id);
    $component->assertRedirect(route('internal.controls.geography.countries.states.cities.show', [$this->country, $this->state, $postOffice]));
});

test('the name must be unique within the state', function (): void {
    PostOffice::factory()->for($this->state)->create(['name' => 'Muar']);
    PostOffice::factory()->create(['name' => 'Kluang']);

    Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.name', 'Muar')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique']);

    Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.name', 'Kluang')
        ->call('save')
        ->assertHasNoErrors();
});

test('the name of a deleted city restores that city', function (): void {
    $deleted = PostOffice::factory()->for($this->state)->create(['name' => 'Muar']);
    $deleted->delete();

    Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.name', 'Muar')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('internal.controls.geography.countries.states.cities.show', [$this->country, $this->state, $deleted]));

    expect($deleted->fresh()->trashed())->toBeFalse();
});

test('the create page leads back to the cities tab of the state', function (): void {
    $tab = route('internal.controls.geography.countries.states.show', ['country' => $this->country, 'state' => $this->state, 'tab' => 'cities']);

    $html = $this->get(route('internal.controls.geography.countries.states.cities.create', [$this->country, $this->state]))
        ->assertOk()
        ->assertSeeInOrder([route('internal.controls.geography.countries.states.show', [$this->country, $this->state]), $tab, __('geography/cities.create.title')], false)
        ->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
});

test('a name with outer spaces is taken by the same name', function (): void {
    PostOffice::factory()->for($this->state)->create(['name' => 'Muar']);

    Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.name', ' Muar ')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique']);
});

test('saving trims the name and collapses its inner spaces', function (): void {
    Livewire::test(Create::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.name', '  Bandar   Muar ')
        ->call('save')
        ->assertHasNoErrors();

    expect(PostOffice::query()->whereBelongsTo($this->state)->sole()->name)->toBe('Bandar Muar');
});
