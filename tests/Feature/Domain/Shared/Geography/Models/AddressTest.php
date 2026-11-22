<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Support\AuditLog;
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

test('the audit entry of a new address leaves out the record that it belongs to and its properties', function (): void {
    $venue = Venue::query()->create(['name' => 'Dewan Besar']);

    $address = $venue->address()->create(['line_1' => '1 Jalan Satu']);

    $changes = Activity::query()->whereMorphedTo('subject', $address)->where('event', 'created')->sole()->attribute_changes;

    expect($changes?->get('attributes'))->toHaveKey('line_1')
        ->not->toHaveKeys(['addressable_type', 'addressable_id', 'properties', 'id', 'created_at', 'updated_at']);
});

test('the audit entry of an address names the address and its fields in the language of the page', function (): void {
    app()->setLocale('ms');
    $venue = Venue::query()->create(['name' => 'Dewan Besar']);

    $address = $venue->address()->create(['line_1' => '1 Jalan Satu']);

    $entry = Activity::query()->whereMorphedTo('subject', $address)->sole();

    expect(AuditLog::subjectLabel($entry))->toBe("Alamat #{$address->id}")
        ->and(AuditLog::fieldLabels($entry))->toContain('Alamat');
});

test('an address leaves its type out of the audit log', function (): void {
    $venue = Venue::query()->create(['name' => 'Dewan Besar']);

    $address = $venue->address()->create(['line_1' => '1 Jalan Satu']);
    $address->update(['type' => 'mailing', 'line_1' => '2 Jalan Dua']);

    expect($address->refresh()->type)->toBe('mailing');

    $entries = Activity::query()->whereMorphedTo('subject', $address)->get();
    $updated = $entries->firstWhere('event', 'updated')->attribute_changes;

    expect($entries)->toHaveCount(2)
        ->and($updated?->get('attributes'))->toHaveKey('line_1', '2 Jalan Dua')
        ->not->toHaveKey('type')
        ->and($updated?->get('old'))->not->toHaveKey('type');

    $entries->each(fn (Activity $entry) => expect($entry->attribute_changes?->get('attributes'))->not->toHaveKey('type'));
});
