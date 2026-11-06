<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
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

test('a postcode with one post office selects it', function (): void {
    Livewire::test(AddressFormHost::class)
        ->set('address.postcode', '84000')
        ->assertSet('address.post_office_id', $this->muar->id);
});

test('a postcode with two post offices lists both and selects neither', function (): void {
    $component = Livewire::test(AddressFormHost::class)->set('address.postcode', '84300');

    $component->assertSet('address.post_office_id', null);

    expect($component->instance()->address->postOffices()->all())
        ->toBe([$this->bukitPasir->id => 'Bukit Pasir', $this->muar->id => 'Muar']);
});

test('a new postcode keeps the city when the city has it', function (): void {
    Livewire::test(AddressFormHost::class)
        ->set('address.postcode', '84000')
        ->set('address.postcode', '84300')
        ->assertSet('address.post_office_id', $this->muar->id);
});

test('an unknown postcode clears the city', function (): void {
    Livewire::test(AddressFormHost::class)
        ->set('address.postcode', '84000')
        ->set('address.postcode', '99999')
        ->assertSet('address.post_office_id', null);
});

test('the postcode is trimmed and upper-cased, and a blank one becomes null', function (): void {
    Livewire::test(AddressFormHost::class)
        ->set('address.postcode', ' 84000 ')
        ->assertSet('address.postcode', '84000')
        ->set('address.postcode', 'ab1')
        ->assertSet('address.postcode', 'AB1')
        ->set('address.postcode', '   ')
        ->assertSet('address.postcode', null);
});

test('the state comes from the city', function (): void {
    $component = Livewire::test(AddressFormHost::class)->set('address.postcode', '84000');

    expect($component->instance()->address->stateName())->toBe('Johor');
});

describe('postcode list', function (): void {
    test('the postcode list stays empty until the search has two characters', function (): void {
        $form = Livewire::test(AddressFormHost::class)->set('address.postcodeSearch', '8')->instance()->address;

        expect($form->postcodes()->all())->toBe([]);
    });

    test('the postcode list has the postcodes that start with the search, each once', function (): void {
        $form = Livewire::test(AddressFormHost::class)->set('address.postcodeSearch', '84')->instance()->address;

        expect($form->postcodes()->all())->toBe(['84000' => '84000', '84300' => '84300']);
    });

    test('the postcode search is trimmed and matches regardless of case', function (): void {
        Postcode::factory()->for($this->kangar)->create(['code' => 'AB1 2CD']);

        $form = Livewire::test(AddressFormHost::class)->set('address.postcodeSearch', ' ab1 ')->instance()->address;

        expect($form->postcodes()->all())->toBe(['AB1 2CD' => 'AB1 2CD']);
    });

    test('a wildcard in the postcode search is plain text', function (): void {
        $form = Livewire::test(AddressFormHost::class)->set('address.postcodeSearch', '%%')->instance()->address;

        expect($form->postcodes()->all())->toBe([]);
    });

    test('the current postcode stays in the postcode list', function (): void {
        $form = Livewire::test(AddressFormHost::class)
            ->set('address.postcode', '01000')
            ->set('address.postcodeSearch', '84')
            ->instance()
            ->address;

        expect($form->postcodes()->all())->toBe(['01000' => '01000', '84000' => '84000', '84300' => '84300']);
    });

    test('a trashed postcode is not listed', function (): void {
        Postcode::query()->where('code', '84000')->delete();

        $form = Livewire::test(AddressFormHost::class)->set('address.postcodeSearch', '84')->instance()->address;

        expect($form->postcodes()->all())->toBe(['84300' => '84300']);
    });

    test('a postcode of a trashed post office is not listed', function (): void {
        $this->muar->delete();

        $form = Livewire::test(AddressFormHost::class)->set('address.postcodeSearch', '84')->instance()->address;

        expect($form->postcodes()->all())->toBe(['84300' => '84300']);
    });
});

test('a required address needs line 1, the postcode, and the city', function (): void {
    Livewire::test(AddressFormHost::class, ['required' => true])
        ->call('save')
        ->assertHasErrors(['address.line_1' => 'required', 'address.postcode' => 'required', 'address.post_office_id' => 'required']);
});

test('an optional address can stay empty', function (): void {
    Livewire::test(AddressFormHost::class)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseEmpty(Address::class);
});

test('a half-filled optional address needs line 1, the postcode, and the city', function (): void {
    Livewire::test(AddressFormHost::class)
        ->set('address.line_2', 'Taman Bunga')
        ->call('save')
        ->assertHasErrors(['address.line_1' => 'required', 'address.postcode' => 'required', 'address.post_office_id' => 'required']);
});

test('the postcode must belong to the city', function (): void {
    Livewire::test(AddressFormHost::class)
        ->set('address.line_1', '1 Jalan Satu')
        ->set('address.postcode', '84000')
        ->set('address.post_office_id', $this->bukitPasir->id)
        ->call('save')
        ->assertHasErrors(['address.postcode' => 'exists']);
});

test('a shared postcode without a city shows one error, on the city', function (): void {
    Livewire::test(AddressFormHost::class)
        ->set('address.line_1', '1 Jalan Satu')
        ->set('address.postcode', '84300')
        ->call('save')
        ->assertHasErrors(['address.post_office_id' => 'required'])
        ->assertHasNoErrors('address.postcode');
});

test('an empty city from the browser becomes null', function (): void {
    Livewire::test(AddressFormHost::class)
        ->set('address.post_office_id', '')
        ->assertSet('address.post_office_id', null)
        ->set('address.post_office_id', (string) $this->muar->id)
        ->assertSet('address.post_office_id', $this->muar->id);
});

test('set fills the form from the saved address, and values returns it', function (): void {
    $venue = Venue::query()->create(['name' => 'Dewan Besar']);
    $venue->address()->create(['line_1' => '1 Jalan Satu', 'postcode' => '84000', 'post_office_id' => $this->muar->id]);

    $component = Livewire::test(AddressFormHost::class, ['venue' => $venue]);

    expect($component->instance()->address->values())->toBe([
        'line_1' => '1 Jalan Satu',
        'line_2' => null,
        'line_3' => null,
        'postcode' => '84000',
        'post_office_id' => $this->muar->id,
    ]);
});

test('save writes to the model from set, with the kept type', function (): void {
    $component = Livewire::test(AddressFormHost::class, ['type' => 'mailing'])
        ->set('address.line_1', '1 Jalan Satu')
        ->set('address.postcode', '84000')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas(Address::class, [
        'addressable_id' => $component->instance()->venue->id,
        'type' => 'mailing',
        'line_1' => '1 Jalan Satu',
        'post_office_id' => $this->muar->id,
    ]);
});

test('save takes the model when set got none, as on a create page', function (): void {
    $component = Livewire::test(AddressFormHost::class, ['create' => true])
        ->set('address.line_1', '1 Jalan Satu')
        ->set('address.postcode', '84000')
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas(Address::class, ['addressable_id' => $component->instance()->venue->id, 'line_1' => '1 Jalan Satu']);
});

test('save without a model fails', function (): void {
    $component = Livewire::test(AddressFormHost::class, ['create' => true])
        ->set('address.line_1', '1 Jalan Satu')
        ->set('address.postcode', '84000');

    expect(fn () => $component->call('save'))->toThrow(LogicException::class);
});

test('copy fills the form from another address, and save still writes to the model from set', function (): void {
    $owner = Venue::query()->create(['name' => 'Pemilik']);
    $owner->address()->create(['line_1' => '9 Jalan Sembilan', 'postcode' => '84000', 'post_office_id' => $this->muar->id]);

    $component = Livewire::test(AddressFormHost::class)
        ->call('copyFrom', $owner->id)
        ->assertSet('address.line_1', '9 Jalan Sembilan')
        ->call('save');

    $this->assertDatabaseCount(Address::class, 2);
    $this->assertDatabaseHas(Address::class, ['addressable_id' => $component->instance()->venue->id, 'line_1' => '9 Jalan Sembilan']);
});

test('a trashed post office is not listed or accepted', function (): void {
    $this->muar->delete();

    Livewire::test(AddressFormHost::class)
        ->set('address.line_1', '1 Jalan Satu')
        ->set('address.postcode', '84000')
        ->assertSet('address.post_office_id', null)
        ->set('address.post_office_id', $this->muar->id)
        ->call('save')
        ->assertHasErrors(['address.post_office_id' => 'exists']);
});

test('a saved address whose city lost its postcode loads and asks for a fix', function (): void {
    $venue = Venue::query()->create(['name' => 'Dewan Besar']);
    $venue->address()->create(['line_1' => '1 Jalan Satu', 'postcode' => '84000', 'post_office_id' => $this->bukitPasir->id]);

    Livewire::test(AddressFormHost::class, ['venue' => $venue])
        ->assertOk()
        ->call('save')
        ->assertHasErrors(['address.postcode' => 'exists']);
});

test('the browser cannot change a locked property', function (string $property, mixed $value): void {
    $component = Livewire::test(AddressFormHost::class);

    expect(fn () => $component->set("address.{$property}", $value))->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    'addressable' => ['addressable', null],
    'type' => ['type', 'mailing'],
    'required' => ['required', true],
    'showingDistricts' => ['showingDistricts', true],
    'districtsRequired' => ['districtsRequired', true],
    'local' => ['local', true],
]);

describe('districts', function (): void {
    test('the district list has the districts of the city state', function (): void {
        $component = Livewire::test(AddressFormHost::class, ['districts' => 'optional'])->set('address.postcode', '84000');

        expect($component->instance()->address->districts()->all())
            ->toBe([$this->batuPahat->id => 'Batu Pahat', $this->muarDistrict->id => 'Muar']);
    });

    test('a state with one district selects it', function (): void {
        Livewire::test(AddressFormHost::class, ['districts' => 'optional'])
            ->set('address.postcode', '01000')
            ->assertSet('address.district_id', $this->perlisDistrict->id);
    });

    test('a city in another state replaces the district and clears the subdistrict', function (): void {
        Livewire::test(AddressFormHost::class, ['districts' => 'optional'])
            ->set('address.postcode', '84000')
            ->set('address.district_id', $this->muarDistrict->id)
            ->set('address.subdistrict_id', $this->sungaiBalang->id)
            ->set('address.postcode', '01000')
            ->assertSet('address.district_id', $this->perlisDistrict->id)
            ->assertSet('address.subdistrict_id', null);
    });

    test('a new district clears the subdistrict', function (): void {
        Livewire::test(AddressFormHost::class, ['districts' => 'optional'])
            ->set('address.postcode', '84000')
            ->set('address.district_id', $this->muarDistrict->id)
            ->set('address.subdistrict_id', $this->sungaiBalang->id)
            ->set('address.district_id', $this->batuPahat->id)
            ->assertSet('address.subdistrict_id', null);
    });

    test('optional districts can stay empty', function (): void {
        Livewire::test(AddressFormHost::class, ['districts' => 'optional'])
            ->set('address.line_1', '1 Jalan Satu')
            ->set('address.postcode', '84000')
            ->call('save')
            ->assertHasNoErrors();
    });

    test('a district must be in the state of the city', function (): void {
        Livewire::test(AddressFormHost::class, ['districts' => 'optional'])
            ->set('address.line_1', '1 Jalan Satu')
            ->set('address.postcode', '84000')
            ->set('address.district_id', $this->perlisDistrict->id)
            ->call('save')
            ->assertHasErrors(['address.district_id' => 'exists']);
    });

    test('a subdistrict must be in the district', function (): void {
        Livewire::test(AddressFormHost::class, ['districts' => 'optional'])
            ->set('address.line_1', '1 Jalan Satu')
            ->set('address.postcode', '84000')
            ->set('address.district_id', $this->batuPahat->id)
            ->set('address.subdistrict_id', $this->sungaiBalang->id)
            ->call('save')
            ->assertHasErrors(['address.subdistrict_id' => 'exists']);
    });

    test('required districts need a district', function (): void {
        Livewire::test(AddressFormHost::class, ['required' => true, 'districts' => 'required'])
            ->set('address.line_1', '1 Jalan Satu')
            ->set('address.postcode', '84000')
            ->call('save')
            ->assertHasErrors(['address.district_id' => 'required']);
    });

    test('a required subdistrict is needed only when the district has subdistricts', function (): void {
        Livewire::test(AddressFormHost::class, ['required' => true, 'districts' => 'required'])
            ->set('address.line_1', '1 Jalan Satu')
            ->set('address.postcode', '84000')
            ->set('address.district_id', $this->batuPahat->id)
            ->call('save')
            ->assertHasNoErrors()
            ->set('address.district_id', $this->muarDistrict->id)
            ->call('save')
            ->assertHasErrors(['address.subdistrict_id' => 'required']);
    });

    test('required districts are not needed for an empty optional address', function (): void {
        Livewire::test(AddressFormHost::class, ['districts' => 'required'])
            ->call('save')
            ->assertHasNoErrors();
    });

    test('hidden district fields leave the district out of the values', function (): void {
        $venue = Venue::query()->create(['name' => 'Dewan Besar']);
        $venue->address()->create(['line_1' => '1 Jalan Satu', 'postcode' => '84000', 'post_office_id' => $this->muar->id, 'district_id' => $this->muarDistrict->id]);

        $component = Livewire::test(AddressFormHost::class, ['venue' => $venue]);

        expect($component->instance()->address->values())->not->toHaveKey('district_id');
    });

    test('a hidden district does not count as a filled address', function (): void {
        $venue = Venue::query()->create(['name' => 'Dewan Besar']);
        $venue->address()->create(['district_id' => $this->muarDistrict->id]);

        Livewire::test(AddressFormHost::class, ['venue' => $venue])
            ->call('save')
            ->assertHasNoErrors();
    });

    test('a trashed district is not listed or accepted', function (): void {
        $this->batuPahat->delete();

        $component = Livewire::test(AddressFormHost::class, ['districts' => 'optional'])
            ->set('address.line_1', '1 Jalan Satu')
            ->set('address.postcode', '84000');

        expect($component->instance()->address->districts()->keys()->all())->not->toContain($this->batuPahat->id);

        $component->set('address.district_id', $this->batuPahat->id)
            ->call('save')
            ->assertHasErrors(['address.district_id' => 'exists']);
    });
});

describe('local area', function (): void {
    beforeEach(function (): void {
        config()->set('address.locality', [
            'country' => $this->johor->country->code,
            'state' => $this->johor->code,
            'district' => $this->muarDistrict->code,
        ]);

        $this->far = PostOffice::factory()->for($this->perlis)->create(['name' => 'Far']);
        Postcode::factory()->for($this->far)->create(['code' => '84000']);
    });

    test('a local address lists only local post offices', function (): void {
        $component = Livewire::test(AddressFormHost::class, ['local' => true])->set('address.postcode', '84000');

        $component->assertSet('address.post_office_id', $this->muar->id);

        expect($component->instance()->address->postOffices()->all())->toBe([$this->muar->id => 'Muar']);
    });

    test('a local address lists only local postcodes, also without a search', function (): void {
        $form = Livewire::test(AddressFormHost::class, ['local' => true])->instance()->address;

        expect($form->postcodes()->all())->toBe(['84000' => '84000', '84300' => '84300']);
    });

    test('a local address searches within the local postcodes', function (): void {
        $form = Livewire::test(AddressFormHost::class, ['local' => true])->set('address.postcodeSearch', '843')->instance()->address;

        expect($form->postcodes()->all())->toBe(['84300' => '84300']);
    });

    test('a postcode outside the local area fails with the local-area error', function (): void {
        $component = Livewire::test(AddressFormHost::class, ['local' => true])
            ->set('address.line_1', '1 Jalan Satu')
            ->set('address.postcode', '01000')
            ->call('save');

        expect($component->errors()->get('address.postcode'))->toBe([__('common/inputs.postcode_outside_local_area')]);
    });

    test('a city outside the local area fails', function (): void {
        Livewire::test(AddressFormHost::class, ['local' => true])
            ->set('address.line_1', '1 Jalan Satu')
            ->set('address.postcode', '01000')
            ->set('address.post_office_id', $this->kangar->id)
            ->call('save')
            ->assertHasErrors('address.post_office_id');
    });

    test('a local address passes', function (): void {
        Livewire::test(AddressFormHost::class, ['local' => true])
            ->set('address.line_1', '1 Jalan Satu')
            ->set('address.postcode', '84000')
            ->call('save')
            ->assertHasNoErrors();
    });

    test('a local city preselects the local district', function (): void {
        Livewire::test(AddressFormHost::class, ['local' => true, 'districts' => 'optional'])
            ->set('address.postcode', '84000')
            ->assertSet('address.district_id', $this->muarDistrict->id);
    });

    test('without local, the local district is not preselected', function (): void {
        Livewire::test(AddressFormHost::class, ['districts' => 'optional'])
            ->set('address.postcode', '84000')
            ->set('address.post_office_id', $this->muar->id)
            ->assertSet('address.district_id', null);
    });

    test('turning on local without a local state fails at once', function (): void {
        $form = Livewire::test(AddressFormHost::class)->instance()->address;
        config()->set('address.locality.state');

        expect(fn () => $form->local())->toThrow(LogicException::class, 'address.locality.state');
    });
});
