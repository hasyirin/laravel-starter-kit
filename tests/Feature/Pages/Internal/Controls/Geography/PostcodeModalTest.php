<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\PostcodeModal;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.create', 'internal.controls.geography.update');
    $this->actingAs($this->actor);
    $this->postOffice = PostOffice::factory()->create(['name' => 'Muar']);
});

test('adding a postcode needs geography create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show')
        ->assertForbidden();
});

test('saving adds a postcode with its location to the city', function (): void {
    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show')
        ->set('form.code', ' 84000 ')
        ->set('form.latitude', 2.0442)
        ->set('form.longitude', 102.5689)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched(PostcodeModal::event())
        ->assertSet('showing', false);

    expect(Postcode::query()->where('code', '84000')->firstOrFail())
        ->post_office_id->toBe($this->postOffice->id)
        ->latitude->toBe(2.0442)
        ->longitude->toBe(102.5689);
});

test('the location is optional', function (): void {
    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show')
        ->set('form.code', '84000')
        ->call('save')
        ->assertHasNoErrors();

    expect(Postcode::query()->where('code', '84000')->firstOrFail()->latitude)->toBeNull();
});

test('editing a postcode can clear its location', function (): void {
    $postcode = Postcode::factory()->create(['post_office_id' => $this->postOffice->id, 'latitude' => 2.0442, 'longitude' => 102.5689]);

    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show', $postcode->id)
        ->assertSet('form.latitude', 2.0442)
        ->set('form.latitude')
        ->set('form.longitude')
        ->call('save')
        ->assertHasNoErrors();

    expect($postcode->fresh()->only('latitude', 'longitude'))->toBe(['latitude' => null, 'longitude' => null]);
});

test('the location needs both values, each on the earth', function (?float $latitude, ?float $longitude, array $errors): void {
    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show')
        ->set('form.code', '84000')
        ->set('form.latitude', $latitude)
        ->set('form.longitude', $longitude)
        ->call('save')
        ->assertHasErrors($errors);
})->with([
    'latitude only' => [2.0442, null, ['form.longitude' => 'required']],
    'longitude only' => [null, 102.5689, ['form.latitude' => 'required']],
    'latitude out of range' => [91.0, 102.5689, ['form.latitude' => 'between']],
    'longitude out of range' => [2.0442, -181.0, ['form.longitude' => 'between']],
]);

test('a postcode of another city is not found', function (): void {
    $postcode = Postcode::factory()->create();

    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show', $postcode->id)
        ->assertNotFound();
});

test('without a city, the modal cannot add a postcode', function (): void {
    Livewire::test(PostcodeModal::class)
        ->call('show')
        ->assertNotFound();
});

test('without a city, the modal edits a postcode under its own city and shows the city', function (): void {
    $postcode = Postcode::factory()->create(['post_office_id' => $this->postOffice->id]);

    Livewire::test(PostcodeModal::class)
        ->call('show', $postcode->id)
        ->assertSee('Muar')
        ->assertSee($this->postOffice->state->name)
        ->set('form.code', '84001')
        ->call('save')
        ->assertHasNoErrors();

    expect($postcode->fresh())
        ->code->toBe('84001')
        ->post_office_id->toBe($this->postOffice->id);
});

test('the code must be unique within the city', function (): void {
    Postcode::factory()->create(['post_office_id' => $this->postOffice->id, 'code' => '84000']);

    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show')
        ->set('form.code', '84000')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('adding the code of a deleted postcode restores it', function (): void {
    $deleted = Postcode::factory()->create(['post_office_id' => $this->postOffice->id, 'code' => '84000']);
    $deleted->delete();

    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show')
        ->set('form.code', '84000')
        ->call('save')
        ->assertHasNoErrors();

    expect($deleted->fresh()->trashed())->toBeFalse();
});

test('editing cannot take the code of a deleted postcode', function (): void {
    $postcode = Postcode::factory()->create(['post_office_id' => $this->postOffice->id, 'code' => '84000']);
    Postcode::factory()->create(['post_office_id' => $this->postOffice->id, 'code' => '84001'])->delete();

    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show', $postcode->id)
        ->set('form.code', '84001')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('a code with outer spaces is taken by the same code', function (): void {
    Postcode::factory()->create(['post_office_id' => $this->postOffice->id, 'code' => '84000']);

    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show')
        ->set('form.code', ' 84000 ')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('the modal is read-only without geography update', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.update');
    $postcode = Postcode::factory()->create(['post_office_id' => $this->postOffice->id, 'code' => '84000']);

    Livewire::test(PostcodeModal::class, ['postOffice' => $this->postOffice])
        ->call('show', $postcode->id)
        ->assertDontSeeHtml('type="submit"')
        ->set('form.code', '84001')
        ->call('save')
        ->assertForbidden();

    expect($postcode->fresh()->code)->toBe('84000');
});
