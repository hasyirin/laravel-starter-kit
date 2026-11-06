<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use Dom\HTMLDocument;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\AddressFormHost;
use Tests\Fixtures\Models\Venue;

beforeEach(function (): void {
    Schema::create('venues', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Relation::morphMap(['venue' => Venue::class]);

    $this->johor = State::factory()->create(['name' => 'Johor']);
    $this->bukitPasir = PostOffice::factory()->for($this->johor)->create(['name' => 'Bukit Pasir']);
    $this->muar = PostOffice::factory()->for($this->johor)->create(['name' => 'Muar']);
    Postcode::factory()->for($this->bukitPasir)->create(['code' => '84300']);
    Postcode::factory()->for($this->muar)->create(['code' => '84300']);
    Postcode::factory()->for($this->muar)->create(['code' => '84000']);

    $this->muarDistrict = District::factory()->for($this->johor)->create(['name' => 'Muar']);
    $this->batuPahat = District::factory()->for($this->johor)->create(['name' => 'Batu Pahat']);
    $this->sungaiBalang = Subdistrict::factory()->for($this->muarDistrict)->create(['name' => 'Sungai Balang']);

    $this->perlis = State::factory()->create(['name' => 'Perlis']);
    $this->kangar = PostOffice::factory()->for($this->perlis)->create(['name' => 'Kangar']);
    Postcode::factory()->for($this->kangar)->create(['code' => '01000']);
    $this->perlisDistrict = District::factory()->for($this->perlis)->create(['name' => 'Perlis']);
});

test('the address binds three lines, the postcode, its search, and the city to the form', function (): void {
    Livewire::test(AddressFormHost::class)
        ->assertSeeHtml('wire:model="address.line_1"')
        ->assertSeeHtml('wire:model="address.line_2"')
        ->assertSeeHtml('wire:model="address.line_3"')
        ->assertSeeHtml('wire:model.live="address.postcode"')
        ->assertSeeHtml('wire:model.live.debounce.250ms="address.postcodeSearch"')
        ->assertSeeHtml('wire:model.live="address.post_office_id"');
});

test('the postcode list shows the search results as options', function (): void {
    Livewire::test(AddressFormHost::class)
        ->assertDontSee('84300')
        ->set('address.postcodeSearch', '843')
        ->assertSee('84300')
        ->assertDontSee('84000');
});

test('the city placeholder says what the city list needs', function (): void {
    Livewire::test(AddressFormHost::class)
        ->assertSee(__('common/inputs.city_needs_postcode'))
        ->set('address.postcode', '99999')
        ->assertSee(__('common/inputs.city_not_found'))
        ->set('address.postcode', '84300')
        ->assertSee(__('common/inputs.city_placeholder'));
});

test('the state shows the state of the city in a disabled input', function (): void {
    $component = Livewire::test(AddressFormHost::class)->set('address.postcode', '84000');

    $state = HTMLDocument::createFromString($component->html(), LIBXML_NOERROR)->querySelector('input[value="Johor"]');

    expect($state?->hasAttribute('disabled'))->toBeTrue();
});

test('the postcode search asks for the number keypad', function (): void {
    $component = Livewire::test(AddressFormHost::class);

    $search = HTMLDocument::createFromString($component->html(), LIBXML_NOERROR)->querySelector('[data-flux-select-search] input');

    expect($search?->getAttribute('inputmode'))->toBe('numeric');
});

test('the district fields show only with withDistricts', function (): void {
    Livewire::test(AddressFormHost::class)->assertDontSeeHtml('address.district_id');

    Livewire::test(AddressFormHost::class, ['districts' => 'optional'])
        ->assertSeeHtml('wire:model.live="address.district_id"');
});

test('the subdistrict field shows only when the district has subdistricts', function (): void {
    Livewire::test(AddressFormHost::class, ['districts' => 'optional'])
        ->set('address.postcode', '84000')
        ->set('address.district_id', $this->batuPahat->id)
        ->assertDontSeeHtml('address.subdistrict_id')
        ->set('address.district_id', $this->muarDistrict->id)
        ->assertSeeHtml('wire:model.live="address.subdistrict_id"');
});
