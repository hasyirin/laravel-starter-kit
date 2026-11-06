<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Address;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\Fixtures\Models\Venue;

beforeEach(function (): void {
    Schema::create('venues', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Relation::morphMap(['venue' => Venue::class]);
});

test('an address stores the morph alias of its model', function (): void {
    $venue = Venue::query()->create(['name' => 'Dewan Besar']);

    $address = $venue->address()->create(['line_1' => '1 Jalan Satu']);

    expect($address)->toBeInstanceOf(Address::class)
        ->and($address->addressable_type)->toBe('venue')
        ->and($address->type)->toBe('primary');
});

test('saving an address writes an audit entry', function (): void {
    $venue = Venue::query()->create(['name' => 'Dewan Besar']);

    $address = $venue->address()->create(['line_1' => '1 Jalan Satu']);

    $this->assertDatabaseHas(Activity::class, ['subject_type' => 'address', 'subject_id' => $address->id, 'event' => 'created']);
});
