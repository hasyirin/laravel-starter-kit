<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\Country\Create;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.create');
    $this->actingAs($this->actor);
});

test('adding a country needs geography create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    $this->get(route('internal.controls.geography.countries.create'))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.create');

    $this->get(route('internal.controls.geography.countries.create'))->assertOk()->assertSeeLivewire(Create::class);
});

test('saving creates the country and opens its page', function (): void {
    $component = Livewire::test(Create::class)
        ->set('form.code', 'SGP')
        ->set('form.name', 'Singapore')
        ->set('form.alpha_2', 'SG')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Country created.');

    $country = Country::query()->where('code', 'SGP')->firstOrFail();

    expect($country->only('name', 'alpha_2'))->toBe(['name' => 'Singapore', 'alpha_2' => 'SG']);
    $component->assertRedirect(route('internal.controls.geography.countries.show', $country));
});

test('the alpha-2 code is optional', function (): void {
    Livewire::test(Create::class)
        ->set('form.code', 'SGP')
        ->set('form.name', 'Singapore')
        ->set('form.alpha_2', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Country::query()->where('code', 'SGP')->firstOrFail()->alpha_2)->toBeNull();
});

test('the code has at most 3 characters and the alpha-2 code exactly 2', function (): void {
    Livewire::test(Create::class)
        ->set('form.code', 'SGPR')
        ->set('form.name', 'Singapore')
        ->set('form.alpha_2', 'S')
        ->call('save')
        ->assertHasErrors(['form.code' => 'max', 'form.alpha_2' => 'size']);
});

test('the code must be unique', function (): void {
    Country::factory()->create(['code' => 'SGP']);

    Livewire::test(Create::class)
        ->set('form.code', 'SGP')
        ->set('form.name', 'Singapore')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('the code of a deleted country restores that country', function (): void {
    $deleted = Country::factory()->create(['code' => 'SGP', 'name' => 'Old Singapore']);
    $deleted->delete();

    Livewire::test(Create::class)
        ->set('form.code', 'SGP')
        ->set('form.name', 'Singapore')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('internal.controls.geography.countries.show', $deleted));

    expect($deleted->fresh())
        ->trashed()->toBeFalse()
        ->name->toBe('Singapore');
});

test('the create page leads back to the countries tab of geography', function (): void {
    $tab = route('internal.controls.geography.index', ['tab' => 'countries']);

    $html = $this->get(route('internal.controls.geography.countries.create'))
        ->assertOk()
        ->assertSeeInOrder([route('internal.controls.geography.index'), $tab, __('geography/countries.create.title')], false)
        ->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
});

test('a code with outer spaces is taken by the same code', function (): void {
    Country::factory()->create(['code' => 'SGP']);

    Livewire::test(Create::class)
        ->set('form.code', ' SGP ')
        ->set('form.name', 'Singapore')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('saving trims the code, the name, and the alpha-2 code and collapses their inner spaces', function (): void {
    Livewire::test(Create::class)
        ->set('form.code', ' SGP ')
        ->set('form.name', '  Republic   of Singapore ')
        ->set('form.alpha_2', ' SG ')
        ->call('save')
        ->assertHasNoErrors();

    expect(Country::query()->where('code', 'SGP')->firstOrFail()->only('name', 'alpha_2'))->toBe(['name' => 'Republic of Singapore', 'alpha_2' => 'SG']);
});
