<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\State\Show;
use App\Livewire\Pages\Internal\Controls\Geography\State\Tabs;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.update');
    $this->actingAs($this->actor);
    $this->country = Country::factory()->create(['name' => 'Singapore']);
    $this->state = State::factory()->for($this->country)->create(['code' => '01', 'name' => 'Johor']);
});

test('viewing a state needs geography view', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.view');

    $this->get(route('internal.controls.geography.countries.states.show', [$this->country, $this->state]))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.view');

    $this->get(route('internal.controls.geography.countries.states.show', [$this->country, $this->state]))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeLivewire(Tabs::class)
        ->assertSee('Singapore')
        ->assertSee('<title>Johor - '.config('app.name').'</title>', false);
});

test('a state of another country is not found', function (): void {
    $other = Country::factory()->create();

    $this->get(route('internal.controls.geography.countries.states.show', [$other, $this->state]))->assertNotFound();
    $this->get(route('internal.controls.geography.countries.states.districts.create', [$other, $this->state]))->assertNotFound();
});

test('the browser cannot change the country of the form', function (): void {
    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.country', Country::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the browser cannot change the state of the form', function (): void {
    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.state', State::factory()->for($this->country)->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('saving changes the code and name', function (): void {
    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.code', '02')
        ->set('form.name', 'Johor Darul Takzim')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'State updated.');

    expect($this->state->fresh()->only('code', 'name'))->toBe(['code' => '02', 'name' => 'Johor Darul Takzim']);
});

test('the code cannot take the code of another state of the country, also a deleted one', function (bool $deleted): void {
    $other = State::factory()->for($this->country)->create(['code' => '02']);

    if ($deleted) {
        $other->delete();
    }

    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.code', '02')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
})->with([
    'live' => [false],
    'deleted' => [true],
]);

test('the code can match a state of another country', function (): void {
    State::factory()->create(['code' => '02']);

    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state])
        ->set('form.code', '02')
        ->call('save')
        ->assertHasNoErrors();
});

test('the form is read-only without geography update', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.update');

    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state])
        ->assertDontSeeHtml('type="submit"')
        ->call('save')
        ->assertForbidden();
});

test('a state leads back to the states tab of its country', function (): void {
    $tab = route('internal.controls.geography.countries.show', ['country' => $this->country, 'tab' => 'states']);

    $html = $this->get(route('internal.controls.geography.countries.states.show', [$this->country, $this->state]))
        ->assertOk()
        ->assertSeeInOrder([route('internal.controls.geography.index'), route('internal.controls.geography.countries.show', $this->country), $tab, 'Johor'], false)
        ->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
});

test('the code of the local state is disabled and a forced change does not save, but its name saves', function (): void {
    config()->set('address.locality', ['country' => $this->country->code, 'state' => '01', 'district' => null]);

    $page = Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state]);

    expect($page->html())->toMatch('/<input(?=[^>]*\sdisabled\b)(?=[^>]*wire:model="form\.code")[^>]*>/');

    $page->assertSee(__('geography/geography.locality_code'))
        ->set('form.code', '99')
        ->set('form.name', 'Johor Darul Takzim')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->state->fresh()->only('code', 'name'))->toBe(['code' => '01', 'name' => 'Johor Darul Takzim']);
});
