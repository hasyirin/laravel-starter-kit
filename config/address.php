<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;

return [
    'locality' => [
        'country' => 'MYS',
        'state' => null,
        'district' => null,
    ],

    'models' => [
        'address' => Address::class,
        'country' => Country::class,
        'state' => State::class,
        'district' => District::class,
        'subdistrict' => Subdistrict::class,
        'post-office' => PostOffice::class,
        'postcode' => Postcode::class,
    ],

    'tables' => [
        'addresses' => 'addresses',
        'countries' => 'countries',
        'states' => 'states',
        'districts' => 'districts',
        'subdistricts' => 'subdistricts',
        'post_offices' => 'post_offices',
        'postcodes' => 'postcodes',
    ],
];
