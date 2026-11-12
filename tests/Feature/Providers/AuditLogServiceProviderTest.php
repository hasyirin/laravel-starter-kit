<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Spatie\Activitylog\Models\Activity;

test('each audit entry stores the trace id', function (): void {
    context()->add('trace_id', 'trace-abc');

    User::factory()->create()->update(['name' => 'Renamed']);

    expect(Activity::query()->latest('id')->firstOrFail()->getProperty('trace_id'))->toBe('trace-abc');
});

test('each audit entry stores the impersonator while impersonating', function (): void {
    $impersonator = User::factory()->create();
    context()->add('impersonator_id', $impersonator->id);

    User::factory()->create()->update(['name' => 'Renamed']);

    expect(Activity::query()->latest('id')->firstOrFail()->getProperty('impersonator_id'))->toBe($impersonator->id);
});

test('no impersonator is stored otherwise', function (): void {
    User::factory()->create()->update(['name' => 'Renamed']);

    expect(Activity::query()->latest('id')->firstOrFail()->properties?->has('impersonator_id'))->toBeFalse();
});

test('each audit entry stores the ip address and the user agent of the request', function (): void {
    context()->addHidden(['ip_address' => '203.0.113.7', 'user_agent' => 'Test Browser']);

    User::factory()->create()->update(['name' => 'Renamed']);

    $entry = Activity::query()->latest('id')->firstOrFail();

    expect($entry->getProperty('ip_address'))->toBe('203.0.113.7')
        ->and($entry->getProperty('user_agent'))->toBe('Test Browser');
});

test('an audit entry from a queued job stores no ip address and no user agent of the request that queued it', function (): void {
    context()->add('trace_id', 'trace-abc');
    context()->addHidden(['ip_address' => '203.0.113.7', 'user_agent' => 'Test Browser']);

    dispatch(function (): void {
        activity()->log('queued');
    });

    $entry = Activity::query()->latest('id')->firstOrFail();

    expect($entry->getProperty('trace_id'))->toBe('trace-abc')
        ->and($entry->properties?->has('ip_address'))->toBeFalse()
        ->and($entry->properties?->has('user_agent'))->toBeFalse();
});

test('an audit entry keeps its own properties', function (): void {
    context()->add('trace_id', 'trace-abc');

    activity()->withProperties(['trace_id' => 'own', 'note' => 'kept'])->log('manual');

    $entry = Activity::query()->latest('id')->firstOrFail();

    expect($entry->getProperty('trace_id'))->toBe('own')
        ->and($entry->getProperty('note'))->toBe('kept');
});
