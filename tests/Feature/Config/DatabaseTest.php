<?php

declare(strict_types=1);

test('the postgres session uses the app time zone, so timestamptz columns read the values that Eloquent writes in it', function (): void {
    expect(config('database.connections.pgsql.timezone'))->toBe(config('app.timezone'));
});
