<?php

declare(strict_types=1);

test('phpunit.xml sets every test value as a server value, so the shell, a container or CI cannot replace it', function (): void {
    $phpunit = simplexml_load_file(base_path('phpunit.xml'));

    expect($phpunit->xpath('//php/server'))->not->toBeEmpty()
        ->and($phpunit->xpath('//php/env'))->toBeEmpty('Change each <env> line in phpunit.xml to <server>.');
});
