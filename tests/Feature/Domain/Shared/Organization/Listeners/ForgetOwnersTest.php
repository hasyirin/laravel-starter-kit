<?php

declare(strict_types=1);

use Illuminate\Database\Events\MigrationsEnded;

beforeEach(function (): void {
    cache()->forever('system.owner.organization', ['id' => 999, 'code' => 'STALE', 'name' => 'Stale', 'is_owner' => true]);
    cache()->forever('system.owner.department', ['id' => 999, 'organization_id' => 999, 'code' => 'STALE', 'name' => 'Stale', 'is_owner' => true]);
    cache()->forever('system.owner.branch', ['id' => 999, 'organization_id' => 999, 'code' => 'STALE', 'name' => 'Stale', 'is_owner' => true]);
});

test('a finished migration run forgets the cached owners', function (): void {
    event(new MigrationsEnded('up'));

    expect(cache()->has('system.owner.organization'))->toBeFalse()
        ->and(cache()->has('system.owner.department'))->toBeFalse()
        ->and(cache()->has('system.owner.branch'))->toBeFalse();
});

test('a finished rollback keeps the cached owners, because the cache table can be gone', function (): void {
    event(new MigrationsEnded('down'));

    expect(cache()->has('system.owner.organization'))->toBeTrue()
        ->and(cache()->has('system.owner.department'))->toBeTrue()
        ->and(cache()->has('system.owner.branch'))->toBeTrue();
});
