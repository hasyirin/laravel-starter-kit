<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;

beforeEach(function (): void {
    $malaysia = Country::factory()->create(['code' => 'MYS', 'name' => 'Malaysia']);
    $brunei = Country::factory()->create(['code' => 'BRN', 'name' => 'Brunei']);

    $johor = State::factory()->for($malaysia)->create(['code' => '01', 'name' => 'Johor']);
    $kedah = State::factory()->for($malaysia)->create(['code' => '02', 'name' => 'Kedah']);
    $belait = State::factory()->for($brunei)->create(['code' => '01', 'name' => 'Belait']);

    $muar = District::factory()->for($johor)->create(['code' => '0101', 'name' => 'Muar']);
    $batuPahat = District::factory()->for($johor)->create(['code' => '0102', 'name' => 'Batu Pahat']);
    $kotaSetar = District::factory()->for($kedah)->create(['code' => '0201', 'name' => 'Kota Setar']);
    $bandar = District::factory()->for($belait)->create(['code' => '0101', 'name' => 'Bandar']);

    Subdistrict::factory()->for($muar)->create(['code' => '01', 'name' => 'Lenga']);
    Subdistrict::factory()->for($batuPahat)->create(['code' => '01', 'name' => 'Minyak Beku']);
    Subdistrict::factory()->for($kotaSetar)->create(['code' => '01', 'name' => 'Alor Malai']);
    Subdistrict::factory()->for($bandar)->create(['code' => '01', 'name' => 'Bangar']);

    Postcode::factory()->for(PostOffice::factory()->for($johor)->create(['name' => 'Muar']))->create(['code' => '84000']);
    Postcode::factory()->for(PostOffice::factory()->for($johor)->create(['name' => 'Batu Pahat']))->create(['code' => '83000']);
    Postcode::factory()->for(PostOffice::factory()->for($kedah)->create(['name' => 'Alor Setar']))->create(['code' => '05000']);
    Postcode::factory()->for(PostOffice::factory()->for($belait)->create(['name' => 'Bangar']))->create(['code' => '01000']);
});

function sortedLocalFirst(string $model, string $column = 'name'): array
{
    return $model::query()->localFirst()->orderBy($column)->pluck($column)->all();
}

test('the configured country sorts first', function (): void {
    config()->set('address.locality', ['country' => 'MYS', 'state' => null, 'district' => null]);

    expect(sortedLocalFirst(Country::class))->toBe(['Malaysia', 'Brunei']);
});

test('the configured state sorts first, or else every state of the configured country', function (?string $state, array $expected): void {
    config()->set('address.locality', ['country' => 'MYS', 'state' => $state, 'district' => null]);

    expect(sortedLocalFirst(State::class))->toBe($expected);
})->with([
    'state configured' => ['01', ['Johor', 'Belait', 'Kedah']],
    'only the country configured' => [null, ['Johor', 'Kedah', 'Belait']],
]);

test('the configured district sorts first, or else the districts of the deepest configured level', function (?string $state, ?string $district, array $expected): void {
    config()->set('address.locality', ['country' => 'MYS', 'state' => $state, 'district' => $district]);

    expect(sortedLocalFirst(District::class))->toBe($expected);
})->with([
    'district configured' => ['01', '0102', ['Batu Pahat', 'Bandar', 'Kota Setar', 'Muar']],
    'state configured' => ['01', null, ['Batu Pahat', 'Muar', 'Bandar', 'Kota Setar']],
    'only the country configured' => [null, null, ['Batu Pahat', 'Kota Setar', 'Muar', 'Bandar']],
]);

test('the subdistricts in the deepest configured level sort first', function (?string $state, ?string $district, array $expected): void {
    config()->set('address.locality', ['country' => 'MYS', 'state' => $state, 'district' => $district]);

    expect(sortedLocalFirst(Subdistrict::class))->toBe($expected);
})->with([
    'district configured' => ['01', '0101', ['Lenga', 'Alor Malai', 'Bangar', 'Minyak Beku']],
    'state configured' => ['01', null, ['Lenga', 'Minyak Beku', 'Alor Malai', 'Bangar']],
    'only the country configured' => [null, null, ['Alor Malai', 'Lenga', 'Minyak Beku', 'Bangar']],
]);

test('the cities and postcodes in the configured state sort first, also when a district is configured', function (string $model, string $column, ?string $state, ?string $district, array $expected): void {
    config()->set('address.locality', ['country' => 'MYS', 'state' => $state, 'district' => $district]);

    expect(sortedLocalFirst($model, $column))->toBe($expected);
})->with([
    'cities, district configured' => [PostOffice::class, 'name', '01', '0101', ['Batu Pahat', 'Muar', 'Alor Setar', 'Bangar']],
    'postcodes, state configured' => [Postcode::class, 'code', '01', null, ['83000', '84000', '01000', '05000']],
]);

test('the cities and postcodes in the configured country sort first when no state is configured', function (string $model, string $column, array $expected): void {
    config()->set('address.locality', ['country' => 'MYS', 'state' => null, 'district' => null]);

    expect(sortedLocalFirst($model, $column))->toBe($expected);
})->with([
    'cities' => [PostOffice::class, 'name', ['Alor Setar', 'Batu Pahat', 'Muar', 'Bangar']],
    'postcodes' => [Postcode::class, 'code', ['05000', '83000', '84000', '01000']],
]);

test('local rows stay first when the query joins the parent table and sorts by its column', function (): void {
    config()->set('address.locality', ['country' => 'MYS', 'state' => '01', 'district' => null]);

    $names = District::query()
        ->select('districts.*')
        ->join('states', 'states.id', '=', 'districts.state_id')
        ->where('districts.name', '!=', 'Muar')
        ->localFirst()
        ->orderBy('states.name')
        ->pluck('districts.name')
        ->all();

    expect($names)->toBe(['Batu Pahat', 'Bandar', 'Kota Setar']);
});

test('nothing sorts first when the configured country does not exist', function (): void {
    config()->set('address.locality', ['country' => 'SGP', 'state' => null, 'district' => null]);

    expect(sortedLocalFirst(State::class))->toBe(['Belait', 'Johor', 'Kedah']);
});
