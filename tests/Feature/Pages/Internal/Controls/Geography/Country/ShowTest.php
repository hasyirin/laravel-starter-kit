<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\Country\Show;
use App\Livewire\Pages\Internal\Controls\Geography\Country\Tabs;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.update');
    $this->actingAs($this->actor);
    $this->country = Country::factory()->create(['code' => 'SGP', 'name' => 'Singapore', 'alpha_2' => 'SG']);
});

test('viewing a country needs geography view', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.view');

    $this->get(route('internal.controls.geography.countries.show', $this->country))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.view');

    $this->get(route('internal.controls.geography.countries.show', $this->country))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeLivewire(Tabs::class)
        ->assertSee('<title>Singapore - '.config('app.name').'</title>', false);
});

test('the browser cannot change the country of the page', function (): void {
    Livewire::test(Show::class, ['country' => $this->country])
        ->set('country', Country::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the browser cannot change the country of the form', function (): void {
    Livewire::test(Show::class, ['country' => $this->country])
        ->set('form.country', Country::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('saving changes the code, name, and alpha-2 code', function (): void {
    Livewire::test(Show::class, ['country' => $this->country])
        ->assertSet('form.code', 'SGP')
        ->set('form.code', 'SIN')
        ->set('form.name', 'Republic of Singapore')
        ->set('form.alpha_2', '')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'Country updated.');

    expect($this->country->fresh()->only('code', 'name', 'alpha_2'))->toBe(['code' => 'SIN', 'name' => 'Republic of Singapore', 'alpha_2' => null]);
});

test('saving keeps the own code of the country', function (): void {
    Livewire::test(Show::class, ['country' => $this->country])
        ->call('save')
        ->assertHasNoErrors();
});

test('the code cannot take the code of another country, also a deleted one', function (bool $deleted): void {
    $other = Country::factory()->create(['code' => 'THA']);

    if ($deleted) {
        $other->delete();
    }

    Livewire::test(Show::class, ['country' => $this->country])
        ->set('form.code', 'THA')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
})->with([
    'live' => [false],
    'deleted' => [true],
]);

test('the form is read-only without geography update', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.update');

    Livewire::test(Show::class, ['country' => $this->country])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($this->country->fresh()->name)->toBe('Singapore');
});

test('a country leads back to the countries tab of geography', function (): void {
    $tab = route('internal.controls.geography.index', ['tab' => 'countries']);

    $html = $this->get(route('internal.controls.geography.countries.show', $this->country))
        ->assertOk()
        ->assertSeeInOrder([route('internal.controls.geography.index'), $tab, 'Singapore'], false)
        ->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
});

test('the code of the local country is disabled and a forced change does not save, but its name saves', function (): void {
    $local = Country::factory()->create(['code' => 'MYS', 'name' => 'Malaysia']);
    config()->set('address.locality', ['country' => 'MYS', 'state' => null, 'district' => null]);

    $page = Livewire::test(Show::class, ['country' => $local]);

    expect($page->html())->toMatch('/<input(?=[^>]*\sdisabled\b)(?=[^>]*wire:model="form\.code")[^>]*>/');

    $page->assertSee(__('geography/geography.locality_code'))
        ->assertSet('form.code', 'MYS')
        ->set('form.code', 'MAS')
        ->set('form.name', 'Persekutuan Malaysia')
        ->call('save')
        ->assertHasNoErrors();

    expect($local->fresh()->only('code', 'name'))->toBe(['code' => 'MYS', 'name' => 'Persekutuan Malaysia']);
});
