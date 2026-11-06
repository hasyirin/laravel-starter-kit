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

test('an audit entry keeps its own properties', function (): void {
    context()->add('trace_id', 'trace-abc');

    activity()->withProperties(['trace_id' => 'own', 'note' => 'kept'])->log('manual');

    $entry = Activity::query()->latest('id')->firstOrFail();

    expect($entry->getProperty('trace_id'))->toBe('own')
        ->and($entry->getProperty('note'))->toBe('kept');
});
