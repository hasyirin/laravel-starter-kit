<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\SaveAddress;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Models\Venue;

beforeEach(function (): void {
    Schema::create('venues', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Relation::morphMap(['venue' => Venue::class]);

    $this->venue = Venue::query()->create(['name' => 'Dewan Besar']);
    $this->johor = State::factory()->create(['name' => 'Johor']);
    $this->muar = PostOffice::factory()->for($this->johor)->create(['name' => 'Muar']);
    Postcode::factory()->for($this->muar)->create(['code' => '84000']);

    $this->values = ['line_1' => '1 Jalan Satu', 'line_2' => null, 'line_3' => null, 'postcode' => '84000', 'post_office_id' => $this->muar->id];
    $this->empty = collect($this->values)->map(fn (): null => null)->all();
});

test('saving creates the address, then updates the same type', function (): void {
    SaveAddress::make()->execute($this->venue, $this->values);

    SaveAddress::make()->execute($this->venue, [...$this->values, 'line_1' => '2 Jalan Dua']);

    $this->assertDatabaseCount(Address::class, 1);
    $this->assertDatabaseHas(Address::class, ['addressable_id' => $this->venue->id, 'type' => 'primary', 'line_1' => '2 Jalan Dua']);
});

test('an address of another type is a separate address', function (): void {
    SaveAddress::make()->execute($this->venue, $this->values);

    SaveAddress::make()->execute($this->venue, $this->values, 'mailing');

    expect(Address::query()->orderBy('type')->pluck('type')->all())->toBe(['mailing', 'primary']);
});

test('the state and the country come from the post office', function (): void {
    $address = SaveAddress::make()->execute($this->venue, $this->values);

    expect($address?->state_id)->toBe($this->johor->id)
        ->and($address?->country_id)->toBe($this->johor->country_id);
});

test('empty values soft-delete the saved address', function (): void {
    $saved = SaveAddress::make()->execute($this->venue, $this->values);

    $result = SaveAddress::make()->execute($this->venue, $this->empty);

    expect($result)->toBeNull();
    $this->assertSoftDeleted($saved);
});

test('a save after a deletion creates a new address', function (): void {
    SaveAddress::make()->execute($this->venue, $this->values);
    SaveAddress::make()->execute($this->venue, $this->empty);

    $address = SaveAddress::make()->execute($this->venue, $this->values);

    $this->assertDatabaseCount(Address::class, 2);
    $this->assertNotSoftDeleted($address);
});

test('empty values create no address when the model has none', function (): void {
    $result = SaveAddress::make()->execute($this->venue, $this->empty);

    expect($result)->toBeNull();
    $this->assertDatabaseEmpty(Address::class);
});

test('a save without district values keeps the saved district while the city stays', function (): void {
    $district = District::factory()->for($this->johor)->create();
    SaveAddress::make()->execute($this->venue, [...$this->values, 'district_id' => $district->id]);

    $address = SaveAddress::make()->execute($this->venue, [...$this->values, 'line_1' => '2 Jalan Dua']);

    expect($address?->district_id)->toBe($district->id);
});

test('a move to another state clears the saved district and subdistrict', function (): void {
    $district = District::factory()->for($this->johor)->create();
    $subdistrict = Subdistrict::factory()->for($district)->create();
    SaveAddress::make()->execute($this->venue, [...$this->values, 'district_id' => $district->id, 'subdistrict_id' => $subdistrict->id]);
    $kangar = PostOffice::factory()->for(State::factory()->create())->create();
    Postcode::factory()->for($kangar)->create(['code' => '01000']);

    $address = SaveAddress::make()->execute($this->venue, [...$this->values, 'postcode' => '01000', 'post_office_id' => $kangar->id]);

    expect($address?->district_id)->toBeNull()
        ->and($address?->subdistrict_id)->toBeNull();
});

test('a save without district values clears the saved district when the city changes in the same state', function (): void {
    $district = District::factory()->for($this->johor)->create();
    $subdistrict = Subdistrict::factory()->for($district)->create();
    SaveAddress::make()->execute($this->venue, [...$this->values, 'district_id' => $district->id, 'subdistrict_id' => $subdistrict->id]);
    $batuPahat = PostOffice::factory()->for($this->johor)->create(['name' => 'Batu Pahat']);
    Postcode::factory()->for($batuPahat)->create(['code' => '83000']);

    $address = SaveAddress::make()->execute($this->venue, [...$this->values, 'postcode' => '83000', 'post_office_id' => $batuPahat->id]);

    expect($address?->district_id)->toBeNull()
        ->and($address?->subdistrict_id)->toBeNull();
});

test('a save with district values keeps them when the city changes in the same state', function (): void {
    $district = District::factory()->for($this->johor)->create();
    $batuPahat = PostOffice::factory()->for($this->johor)->create(['name' => 'Batu Pahat']);
    Postcode::factory()->for($batuPahat)->create(['code' => '83000']);
    SaveAddress::make()->execute($this->venue, $this->values);

    $address = SaveAddress::make()->execute($this->venue, [...$this->values, 'postcode' => '83000', 'post_office_id' => $batuPahat->id, 'district_id' => $district->id, 'subdistrict_id' => null]);

    expect($address?->district_id)->toBe($district->id);
});

describe('a save without location values', function (): void {
    beforeEach(function (): void {
        SaveAddress::make()->execute($this->venue, [...$this->values, 'latitude' => 2.0442, 'longitude' => 102.5689]);
    });

    test('keeps the saved location when the address is the same', function (): void {
        $address = SaveAddress::make()->execute($this->venue, $this->values);

        expect($address)->latitude->toBe(2.0442)->longitude->toBe(102.5689);
    });

    test('clears the saved location when the address moves', function (string $key, mixed $value): void {
        if ($key === 'post_office_id') {
            $value = PostOffice::factory()->for($this->johor)->create(['name' => 'Parit Jawa'])->id;
            Postcode::factory()->create(['code' => '84000', 'post_office_id' => $value]);
        }

        $address = SaveAddress::make()->execute($this->venue, [...$this->values, $key => $value]);

        expect($address)->latitude->toBeNull()->longitude->toBeNull();
    })->with([
        'line 1' => ['line_1', '2 Jalan Dua'],
        'line 2' => ['line_2', 'Taman Sri'],
        'postcode' => ['postcode', '84300'],
        'post office' => ['post_office_id', null],
    ]);
});

test('a save with location values writes them, also when the address moves', function (): void {
    SaveAddress::make()->execute($this->venue, [...$this->values, 'latitude' => 2.0442, 'longitude' => 102.5689]);

    $address = SaveAddress::make()->execute($this->venue, [...$this->values, 'line_1' => '2 Jalan Dua', 'latitude' => 2.05, 'longitude' => 102.57]);

    expect($address)->latitude->toBe(2.05)->longitude->toBe(102.57);
});
