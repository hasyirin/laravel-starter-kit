<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\Geography\SubdistrictModal;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.create', 'internal.controls.geography.update');
    $this->actingAs($this->actor);
    $this->district = District::factory()->create(['name' => 'Batu Pahat']);
});

test('adding a subdistrict needs geography create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    Livewire::test(SubdistrictModal::class, ['district' => $this->district])
        ->call('show')
        ->assertForbidden();
});

test('saving adds a subdistrict to the district', function (): void {
    Livewire::test(SubdistrictModal::class, ['district' => $this->district])
        ->call('show')
        ->set('form.code', '010101')
        ->set('form.name', 'Linau')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched(SubdistrictModal::event())
        ->assertSet('showing', false);

    expect(Subdistrict::query()->where('code', '010101')->firstOrFail()->district_id)->toBe($this->district->id);
});

test('saving changes the code and name of a subdistrict', function (): void {
    $subdistrict = Subdistrict::factory()->for($this->district)->create(['code' => '010101']);

    Livewire::test(SubdistrictModal::class, ['district' => $this->district])
        ->call('show', $subdistrict->id)
        ->assertSet('form.code', '010101')
        ->set('form.code', '010102')
        ->set('form.name', 'Simpang Kanan')
        ->call('save')
        ->assertHasNoErrors();

    expect($subdistrict->fresh()->only('code', 'name'))->toBe(['code' => '010102', 'name' => 'Simpang Kanan']);
});

test('a subdistrict of another district is not found', function (): void {
    $subdistrict = Subdistrict::factory()->create();

    Livewire::test(SubdistrictModal::class, ['district' => $this->district])
        ->call('show', $subdistrict->id)
        ->assertNotFound();
});

test('without a district, the modal cannot add a subdistrict', function (): void {
    Livewire::test(SubdistrictModal::class)
        ->call('show')
        ->assertNotFound();
});

test('without a district, the modal edits a subdistrict under its own district and shows the district', function (): void {
    $subdistrict = Subdistrict::factory()->for($this->district)->create();

    Livewire::test(SubdistrictModal::class)
        ->call('show', $subdistrict->id)
        ->assertSee('Batu Pahat')
        ->assertSee($this->district->state->name)
        ->set('form.name', 'Linau')
        ->call('save')
        ->assertHasNoErrors();

    expect($subdistrict->fresh())
        ->name->toBe('Linau')
        ->district_id->toBe($this->district->id);
});

test('the code must be unique within the district', function (): void {
    Subdistrict::factory()->for($this->district)->create(['code' => '010101']);

    Livewire::test(SubdistrictModal::class, ['district' => $this->district])
        ->call('show')
        ->set('form.code', '010101')
        ->set('form.name', 'Linau')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('adding the code of a deleted subdistrict restores it', function (): void {
    $deleted = Subdistrict::factory()->for($this->district)->create(['code' => '010101']);
    $deleted->delete();

    Livewire::test(SubdistrictModal::class, ['district' => $this->district])
        ->call('show')
        ->set('form.code', '010101')
        ->set('form.name', 'Linau')
        ->call('save')
        ->assertHasNoErrors();

    expect($deleted->fresh())
        ->trashed()->toBeFalse()
        ->name->toBe('Linau');
});

test('editing cannot take the code of a deleted subdistrict', function (): void {
    $subdistrict = Subdistrict::factory()->for($this->district)->create(['code' => '010101']);
    Subdistrict::factory()->for($this->district)->create(['code' => '010102'])->delete();

    Livewire::test(SubdistrictModal::class, ['district' => $this->district])
        ->call('show', $subdistrict->id)
        ->set('form.code', '010102')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('the modal is read-only without geography update', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.update');
    $subdistrict = Subdistrict::factory()->for($this->district)->create(['name' => 'Linau']);

    Livewire::test(SubdistrictModal::class, ['district' => $this->district])
        ->call('show', $subdistrict->id)
        ->assertDontSeeHtml('type="submit"')
        ->set('form.name', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($subdistrict->fresh()->name)->toBe('Linau');
});

test('a code with outer spaces is taken by the same code', function (): void {
    Subdistrict::factory()->for($this->district)->create(['code' => '010101']);

    Livewire::test(SubdistrictModal::class, ['district' => $this->district])
        ->call('show')
        ->set('form.code', ' 010101 ')
        ->set('form.name', 'Linau')
        ->call('save')
        ->assertHasErrors(['form.code' => 'unique']);
});

test('saving trims the code and the name and collapses their inner spaces', function (): void {
    Livewire::test(SubdistrictModal::class, ['district' => $this->district])
        ->call('show')
        ->set('form.code', ' 010101 ')
        ->set('form.name', '  Sungai   Linau ')
        ->call('save')
        ->assertHasNoErrors();

    expect(Subdistrict::query()->where('code', '010101')->firstOrFail()->name)->toBe('Sungai Linau');
});
