<?php

declare(strict_types=1);

test('the postgres session uses the app time zone, so timestamptz columns read the values that Eloquent writes in it', function (): void {
    expect(config('database.connections.pgsql.timezone'))->toBe(config('app.timezone'));
});

test('every connection hides the values of a query in its error messages, so the logs do not show personal data', function (): void {
    $unmasked = collect(config()->array('database.connections'))
        ->reject(fn (array $connection): bool => ($connection['mask_bindings_in_exception_messages'] ?? false) === true)
        ->keys();

    expect($unmasked)->toBeEmpty("Set mask_bindings_in_exception_messages on {$unmasked->implode(', ')}.");
});
