<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\State\Create;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.create');
    $this->actingAs($this->actor);
    $this->country = Country::factory()->create();
});

test('adding a state needs geography create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    $this->get(route('internal.controls.geography.countries.states.create', $this->country))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.create');

    $this->get(route('internal.controls.geography.countries.states.create', $this->country))->assertOk()->assertSeeLivewire(Create::class);
});

test('saving creates the state in the country and opens its page', function (): void {
    $component = Livewire::test(Create::class, ['country' => $this->country])
        ->set('form.code', '01')
        ->set('form.name', 'Johor')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'State created.');

    $state = State::query()->where('code', '01')->firstOrFail();

    expect($state->country_id)->toBe($this->country->id);
    $component->assertRedirect(route('internal.controls.geography.countries.states.show', [$this->country, $state]));
});

test('the code must be unique within the country', function (): void {
    State::factory()->for($this->country)->create(['code' => '01']);
    State::factory()->create(['code' => '02']);

    Livewire::test(Create::class, ['country' => $this->country])
        ->set('form.code', '01')
        ->set('form.name', 'Johor')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);

    Livewire::test(Create::class, ['country' => $this->country])
        ->set('form.code', '02')
        ->set('form.name', 'Kedah')
        ->call('save')
        ->assertHasNoErrors();
});

test('the code of a deleted state restores that state', function (): void {
    $deleted = State::factory()->for($this->country)->create(['code' => '01', 'name' => 'Old Johor']);
    $deleted->delete();

    Livewire::test(Create::class, ['country' => $this->country])
        ->set('form.code', '01')
        ->set('form.name', 'Johor')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('internal.controls.geography.countries.states.show', [$this->country, $deleted]));

    expect($deleted->fresh())
        ->trashed()->toBeFalse()
        ->name->toBe('Johor');
});

test('the browser cannot change the country of the form', function (): void {
    Livewire::test(Create::class, ['country' => $this->country])->set('form.country', Country::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the create page leads back to the states tab of the country', function (): void {
    $tab = route('internal.controls.geography.countries.show', ['country' => $this->country, 'tab' => 'states']);

    $html = $this->get(route('internal.controls.geography.countries.states.create', $this->country))
        ->assertOk()
        ->assertSeeInOrder([route('internal.controls.geography.countries.show', $this->country), $tab, __('geography/states.create.title')], false)
        ->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
});

test('a code with outer spaces is taken by the same code', function (): void {
    State::factory()->for($this->country)->create(['code' => '01']);

    Livewire::test(Create::class, ['country' => $this->country])
        ->set('form.code', ' 01 ')
        ->set('form.name', 'Johor')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('saving trims the code and the name and collapses their inner spaces', function (): void {
    Livewire::test(Create::class, ['country' => $this->country])
        ->set('form.code', ' 01 ')
        ->set('form.name', '  Negeri   Johor ')
        ->call('save')
        ->assertHasNoErrors();

    expect(State::query()->where('code', '01')->firstOrFail()->name)->toBe('Negeri Johor');
});
