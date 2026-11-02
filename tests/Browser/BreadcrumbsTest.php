<?php

declare(strict_types=1);

test('the dropdown of a long trail opens and shows the hidden levels', function (): void {
    $page = visit('/docs/components/breadcrumbs');

    expect($page->script(browserScript(<<<'SCRIPT'
        const dropdown = document.querySelector('nav ui-dropdown');
        const menu = dropdown.querySelector('[data-flux-navmenu]');
        const hiddenAtFirst = ! menu.checkVisibility();

        dropdown.querySelector('button').click();

        return hiddenAtFirst && await until(() => menu.checkVisibility()) && menu.querySelectorAll('a').length > 0;
        SCRIPT)))->toBeTrue();
});

test('a long trail wraps on a phone and the page does not scroll sideways', function (): void {
    $page = visit('/docs/components/breadcrumbs')->resize(360, 720);

    expect($page->script(browserScript(<<<'SCRIPT'
        const trails = [...document.querySelectorAll('nav > [data-flux-breadcrumbs]')];

        return trails.length > 0 && trails.every((trail) => trail.scrollWidth <= trail.clientWidth);
        SCRIPT)))->toBeTrue();
});
