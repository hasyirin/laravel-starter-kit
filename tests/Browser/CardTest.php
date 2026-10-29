<?php

declare(strict_types=1);

function cardScript(string $body): string
{
    $helpers = <<<'SCRIPT'
        const toggle = (heading) => [...document.querySelectorAll('[data-card-toggle]')].find((button) => button.textContent.trim() === heading);
        const card = (heading) => toggle(heading).closest('[data-flux-card]');
        const bodyShown = (heading) => ! card(heading).querySelector(':scope > [data-flux-card-body]').hasAttribute('hidden');
        const folded = (heading) => toggle(heading).getAttribute('aria-expanded') === 'false' && ! bodyShown(heading);
        const opened = (heading) => toggle(heading).getAttribute('aria-expanded') === 'true' && bodyShown(heading);
        SCRIPT;

    return browserScript("{$helpers}\n{$body}");
}

test('an accordion card folds and opens from its header', function (): void {
    $page = visit('/docs/components/card');

    expect($page->script(cardScript(<<<'SCRIPT'
        toggle('Address').click();
        const wasFolded = await until(() => folded('Address'));

        toggle('Address').click();

        return wasFolded && await until(() => opened('Address'));
        SCRIPT)))->toBeTrue();
});

test('a click on a card action does not fold the card', function (): void {
    $page = visit('/docs/components/card');

    expect($page->script(cardScript(<<<'SCRIPT'
        card('Address').querySelector('[data-flux-card-actions] button').click();
        await new Promise((resolve) => setTimeout(resolve, 400));

        return opened('Address');
        SCRIPT)))->toBeTrue();
});

test('a collapsed card starts folded', function (): void {
    $page = visit('/docs/components/card');

    expect($page->script(cardScript("return folded('Notes');")))->toBeTrue();
});

test('folding a card moves the focus from a field inside it to the toggle', function (): void {
    $page = visit('/docs/components/card');

    expect($page->script(cardScript(<<<'SCRIPT'
        card('Address').querySelector('input').focus();
        toggle('Address').click();
        await until(() => folded('Address'));

        return document.activeElement === toggle('Address');
        SCRIPT)))->toBeTrue();
});

test('a folded card opens when the browser rejects a field inside it', function (): void {
    $page = visit('/docs/components/card');

    expect($page->script(cardScript(<<<'SCRIPT'
        toggle('Company').click();
        await until(() => folded('Company'));

        card('Company').closest('form').querySelector('[type="submit"]').click();

        return await until(() => opened('Company'));
        SCRIPT)))->toBeTrue();
});
