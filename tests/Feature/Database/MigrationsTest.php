<?php

declare(strict_types=1);

$migrations = collect(glob(dirname(__DIR__, 3).'/database/migrations/*.php') ?: [])
    ->merge(glob(dirname(__DIR__, 3).'/stubs/migration*.stub') ?: [])
    ->mapWithKeys(fn (string $path): array => [basename($path, '.php') => [$path]])
    ->all();

test('every timestamp column keeps its time zone', function (string $migration): void {
    expect((string) file_get_contents($migration))->not->toMatch('/->(timestamp|timestamps|nullableTimestamps|softDeletes|softDeletesDatetime|dateTime|datetimes)\(/i');
})->with($migrations);
