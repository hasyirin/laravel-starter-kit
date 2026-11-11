<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\District\Show;
use App\Livewire\Pages\Internal\Controls\Geography\District\Tabs;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.update');
    $this->actingAs($this->actor);
    $this->district = District::factory()->create(['code' => '0101', 'name' => 'Batu Pahat']);
    $this->state = $this->district->state;
    $this->country = $this->state->country;
});

test('viewing a district needs geography view', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.view');

    $this->get(route('internal.controls.geography.countries.states.districts.show', [$this->country, $this->state, $this->district]))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.view');

    $this->get(route('internal.controls.geography.countries.states.districts.show', [$this->country, $this->state, $this->district]))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeLivewire(Tabs::class)
        ->assertSee('<title>Batu Pahat - '.config('app.name').'</title>', false);
});

test('a district of another state is not found', function (): void {
    $other = State::factory()->for($this->country)->create();

    $this->get(route('internal.controls.geography.countries.states.districts.show', [$this->country, $other, $this->district]))->assertNotFound();
});

test('the browser cannot change the state of the form', function (): void {
    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'district' => $this->district])
        ->set('form.state', State::factory()->for($this->country)->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the browser cannot change the district of the form', function (): void {
    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'district' => $this->district])
        ->set('form.district', District::factory()->for($this->state)->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('saving changes the code and name', function (): void {
    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'district' => $this->district])
        ->set('form.code', '0102')
        ->set('form.name', 'Muar')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'District updated.');

    expect($this->district->fresh()->only('code', 'name'))->toBe(['code' => '0102', 'name' => 'Muar']);
});

test('the code cannot take the code of another district of the state, also a deleted one', function (bool $deleted): void {
    $other = District::factory()->for($this->state)->create(['code' => '0102']);

    if ($deleted) {
        $other->delete();
    }

    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'district' => $this->district])
        ->set('form.code', '0102')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
})->with([
    'live' => [false],
    'deleted' => [true],
]);

test('the form is read-only without geography update', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.update');

    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'district' => $this->district])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($this->district->fresh()->name)->toBe('Batu Pahat');
});

test('the code of the local district is disabled and a forced change does not save, but its name saves', function (): void {
    config()->set('address.locality', ['country' => $this->country->code, 'state' => $this->state->code, 'district' => '0101']);

    $page = Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'district' => $this->district]);

    expect($page->html())->toMatch('/<input(?=[^>]*\sdisabled\b)(?=[^>]*wire:model="form\.code")[^>]*>/');

    $page->assertSee(__('geography/geography.locality_code'))
        ->set('form.code', '9999')
        ->set('form.name', 'Bandar Penggaram')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->district->fresh()->only('code', 'name'))->toBe(['code' => '0101', 'name' => 'Bandar Penggaram']);
});
