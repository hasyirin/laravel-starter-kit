<?php

declare(strict_types=1);

test('a deferred table loads with its rows and opens the action menu of a row', function (): void {
    $page = visit('/docs/components/data-table');

    $page->assertSee('Malaysia');

    $page->click('.fi-ta-row:first-child .fi-ta-actions .fi-dropdown-trigger button');

    expect($page->script(browserScript(<<<'SCRIPT'
        return await until(() => [...document.querySelectorAll('.fi-dropdown-list-item')].some((item) => item.checkVisibility()));
        SCRIPT)))->toBeTrue();
});

test('a click on a row runs its view action', function (): void {
    $page = visit('/docs/components/data-table');

    $page->assertSee('Malaysia');
    $page->script(browserScript(<<<'SCRIPT'
        document.querySelector('.fi-ta-row .fi-ta-text').click();
        SCRIPT));

    $page->assertSee(__('docs.examples.data_table.viewed'));
});
