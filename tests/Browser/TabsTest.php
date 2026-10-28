<?php

declare(strict_types=1);

const TABS_PAGE = '/docs/components/tabs';

function tabsScript(string $body): string
{
    $helpers = <<<'SCRIPT'
        const rootOf = (key) => document.querySelector(`[data-tabs-key="${key}"]`).closest('[wire\\:id]');
        const tab = (key, slug) => document.querySelector(`[data-tabs-key="${key}"] [data-tab="${slug}"]`);
        const panel = (key, slug) => [...rootOf(key).querySelectorAll(`[data-tab-panel="${slug}"]`)]
            .find((element) => element.closest('[wire\\:id]') === rootOf(key)) ?? null;
        const shown = (key, slug) => panel(key, slug)?.checkVisibility() ?? false;
        const tick = () => new Promise((resolve) => setTimeout(resolve, 50));
        const network = { started: 0, finished: 0 };
        const slowNetwork = (milliseconds) => {
            const fetchOriginal = window.fetch;
            window.fetch = async (...args) => {
                network.started++;
                await new Promise((resolve) => setTimeout(resolve, milliseconds));

                try {
                    return await fetchOriginal(...args);
                } finally {
                    network.finished++;
                }
            };
        };
        const until = async (check) => {
            for (let attempt = 0; attempt < 100; attempt++) {
                if (check()) {
                    return true;
                }

                await new Promise((resolve) => setTimeout(resolve, 100));
            }

            return false;
        };
        const settled = async () => {
            for (let round = 0; round < 3; round++) {
                await until(() => network.started > 0 && network.started === network.finished);
                await new Promise((resolve) => setTimeout(resolve, 300));
            }
        };
        SCRIPT;

    return "async () => {\n{$helpers}\n{$body}\n}";
}

beforeEach(function (): void {
    app()->detectEnvironment(fn (): string => 'local');
});

test('a tab click swaps only the content and sets only its own query key', function (): void {
    $page = visit(TABS_PAGE.'?search=bob&filters[status]=open');

    $page->script('() => { window.samePage = true; }');

    expect($page->script(tabsScript(<<<'SCRIPT'
        tab('intro', 'notes').click();

        return await until(() => shown('intro', 'notes') && ! shown('intro', 'profile'))
            && tab('intro', 'notes').getAttribute('aria-selected') === 'true'
            && window.samePage === true;
        SCRIPT)))->toBeTrue()
        ->and($page->script('() => decodeURIComponent(location.search)'))->toBe('?search=bob&filters[status]=open&intro=notes');

    $page->assertNoJavaScriptErrors();
});

test('a tab click selects the tab with one request', function (): void {
    $page = visit(TABS_PAGE);

    $calls = $page->script(tabsScript(<<<'SCRIPT'
        const sent = [];
        const fetchOriginal = window.fetch;
        window.fetch = (...args) => {
            const components = JSON.parse(args[1]?.body ?? '{}').components ?? [];
            const id = rootOf('intro').getAttribute('wire:id');
            sent.push(...components.filter((component) => JSON.parse(component.snapshot).memo.id === id));

            return fetchOriginal(...args);
        };

        tab('intro', 'notes').click();
        await until(() => shown('intro', 'notes'));

        return sent.map((component) => [...component.calls.map((call) => call.method), ...Object.keys(component.updates)]);
        SCRIPT));

    expect($calls)->toBe([['switchTab']]);
});

test('back and forward restore the tab', function (): void {
    $page = visit(TABS_PAGE);

    $page->script(tabsScript(<<<'SCRIPT'
        tab('intro', 'notes').click();
        await until(() => shown('intro', 'notes'));
        SCRIPT));

    $page->back();

    expect($page->script(tabsScript("return await until(() => shown('intro', 'profile'));")))->toBeTrue();
    $page->assertQueryStringMissing('intro');

    $page->forward();

    expect($page->script(tabsScript("return await until(() => shown('intro', 'notes'));")))->toBeTrue();
    $page->assertQueryStringHas('intro', 'notes');
});

test('a hover link keeps the current query parameters', function (): void {
    $page = visit(TABS_PAGE.'?search=bob');

    $page->script(tabsScript(<<<'SCRIPT'
        history.replaceState(null, '', `${location.pathname}?search=bob&page=2`);
        SCRIPT));

    $page->hover('[data-tabs-key="intro"] [data-tab="notes"]');

    expect($page->attribute('[data-tabs-key="intro"] [data-tab="notes"]', 'href'))
        ->toEndWith('?search=bob&page=2&intro=notes');
});

test('a kept-alive panel shows at once and keeps its input', function (): void {
    $page = visit(TABS_PAGE);

    expect($page->script(tabsScript(<<<'SCRIPT'
        panel('keep-alive', 'profile').querySelector('input').value = 'kept value';

        tab('keep-alive', 'notes').click();
        await until(() => shown('keep-alive', 'notes'));

        slowNetwork(1000);
        tab('keep-alive', 'profile').click();
        await tick();
        const shownAtOnce = shown('keep-alive', 'profile') && ! shown('keep-alive', 'notes');

        return shownAtOnce && panel('keep-alive', 'profile').querySelector('input').value === 'kept value';
        SCRIPT)))->toBeTrue();
});

test('the current panel stays until a panel that is not rendered arrives', function (): void {
    $page = visit(TABS_PAGE);

    expect($page->script(tabsScript(<<<'SCRIPT'
        slowNetwork(500);
        tab('keep-alive', 'notes').click();
        await tick();
        const stayed = shown('keep-alive', 'profile') && panel('keep-alive', 'notes') === null;

        return stayed && await until(() => shown('keep-alive', 'notes') && ! shown('keep-alive', 'profile'));
        SCRIPT)))->toBeTrue();
});

test('a quick click back wins over a pending switch', function (): void {
    $page = visit(TABS_PAGE);

    expect($page->script(tabsScript(<<<'SCRIPT'
        slowNetwork(300);
        tab('keep-alive', 'notes').click();
        await tick();
        tab('keep-alive', 'profile').click();
        await settled();

        return shown('keep-alive', 'profile')
            && ! shown('keep-alive', 'notes')
            && tab('keep-alive', 'profile').getAttribute('aria-selected') === 'true';
        SCRIPT)))->toBeTrue();

    $page->assertQueryStringMissing('keep-alive');
});

test('a load-fresh panel starts empty after a return', function (): void {
    $page = visit(TABS_PAGE);

    expect($page->script(tabsScript(<<<'SCRIPT'
        tab('keep-alive', 'notes').click();
        await until(() => shown('keep-alive', 'notes'));
        panel('keep-alive', 'notes').querySelector('input').value = 'fresh value';

        tab('keep-alive', 'profile').click();
        await until(() => shown('keep-alive', 'profile'));
        tab('keep-alive', 'notes').click();
        await until(() => shown('keep-alive', 'notes') && ! shown('keep-alive', 'profile'));

        return panel('keep-alive', 'notes').querySelector('input').value;
        SCRIPT)))->toBe('');
});

test('a nested tab set keeps the outer key and survives a reload', function (): void {
    $page = visit(TABS_PAGE);

    $page->script(tabsScript(<<<'SCRIPT'
        tab('outer', 'settings').click();
        await until(() => tab('outer-settings', 'notes') !== null);
        tab('outer-settings', 'notes').click();
        await until(() => shown('outer-settings', 'notes'));
        SCRIPT));

    $page->assertQueryStringHas('outer', 'settings')
        ->assertQueryStringHas('outer-settings', 'notes')
        ->refresh()
        ->assertAttribute('[data-tabs-key="outer-settings"] [data-tab="notes"]', 'aria-selected', 'true');
});

test('a disabled tab does not switch', function (): void {
    $page = visit(TABS_PAGE);

    expect($page->script(tabsScript(<<<'SCRIPT'
        tab('intro', 'billing').click();
        await new Promise((resolve) => setTimeout(resolve, 500));

        return shown('intro', 'profile');
        SCRIPT)))->toBeTrue();

    $page->assertQueryStringMissing('intro');
});

test('the navbar marks the current item', function (): void {
    $page = visit(TABS_PAGE);

    $page->script(tabsScript(<<<'SCRIPT'
        tab('navbar', 'notes').click();
        await until(() => shown('navbar', 'notes'));
        SCRIPT));

    $page->assertAttribute('[data-tabs-key="navbar"] [data-tab="notes"]', 'aria-current', 'page')
        ->assertAttributeMissing('[data-tabs-key="navbar"] [data-tab="profile"]', 'aria-current');
});

test('arrow keys move the focus in a tab bar', function (): void {
    $page = visit(TABS_PAGE);

    $page->keys('[data-tabs-key="intro"] [data-tab="profile"]', 'ArrowRight');

    expect($page->script('() => document.activeElement?.dataset.tab'))->toBe('notes');
});
