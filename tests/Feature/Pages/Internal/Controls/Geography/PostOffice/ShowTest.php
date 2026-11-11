<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\PostOffice\Show;
use App\Livewire\Pages\Internal\Controls\Geography\PostOffice\Tabs;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.update');
    $this->actingAs($this->actor);
    $this->postOffice = PostOffice::factory()->create(['name' => 'Muar']);
    $this->state = $this->postOffice->state;
    $this->country = $this->state->country;
});

test('viewing a city needs geography view', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.view');

    $this->get(route('internal.controls.geography.countries.states.cities.show', [$this->country, $this->state, $this->postOffice]))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.view');

    $this->get(route('internal.controls.geography.countries.states.cities.show', [$this->country, $this->state, $this->postOffice]))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeLivewire(Tabs::class)
        ->assertSee('<title>Muar - '.config('app.name').'</title>', false);
});

test('a city of another state is not found', function (): void {
    $other = State::factory()->for($this->country)->create();

    $this->get(route('internal.controls.geography.countries.states.cities.show', [$this->country, $other, $this->postOffice]))->assertNotFound();
});

test('the browser cannot change the state of the form', function (): void {
    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'postOffice' => $this->postOffice])
        ->set('form.state', State::factory()->for($this->country)->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('the browser cannot change the city of the form', function (): void {
    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'postOffice' => $this->postOffice])
        ->set('form.postOffice', PostOffice::factory()->for($this->state)->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('saving changes the name', function (): void {
    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'postOffice' => $this->postOffice])
        ->set('form.name', 'Bandar Maharani')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'City updated.');

    expect($this->postOffice->fresh()->name)->toBe('Bandar Maharani');
});

test('the name cannot take the name of another city of the state, also a deleted one', function (bool $deleted): void {
    $other = PostOffice::factory()->for($this->state)->create(['name' => 'Kluang']);

    if ($deleted) {
        $other->delete();
    }

    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'postOffice' => $this->postOffice])
        ->set('form.name', 'Kluang')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique']);
})->with([
    'live' => [false],
    'deleted' => [true],
]);

test('the form is read-only without geography update', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.update');

    Livewire::test(Show::class, ['country' => $this->country, 'state' => $this->state, 'postOffice' => $this->postOffice])
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($this->postOffice->fresh()->name)->toBe('Muar');
});
