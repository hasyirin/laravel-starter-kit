<?php

declare(strict_types=1);

test('phpunit.xml sets every test value as a server value, so the shell, a container or CI cannot replace it', function (): void {
    $phpunit = simplexml_load_file(base_path('phpunit.xml'));

    expect($phpunit->xpath('//php/server'))->not->toBeEmpty()
        ->and($phpunit->xpath('//php/env'))->toBeEmpty('Change each <env> line in phpunit.xml to <server>.');
});

test('phpunit.xml sets every queue name of .env.example to sync, so a test never puts a job on a real queue', function (): void {
    $phpunit = simplexml_load_file(base_path('phpunit.xml'));

    $names = str((string) file_get_contents(base_path('.env.example')))->matchAll('/^(\w*QUEUE_CONNECTION)=/m');
    $onSync = collect($phpunit->xpath('//php/server[@value="sync"]'))->map(fn (SimpleXMLElement $server): string => (string) $server['name']);

    expect($names)->not->toBeEmpty()
        ->and($names->diff($onSync)->values()->all())->toBe([], 'Set each of these names to sync in phpunit.xml.');
});
