<?php

declare(strict_types=1);

function fieldBlurScript(string $body): string
{
    $helpers = <<<'SCRIPT'
        const group = control('form.carrier');
        const [first, second] = group.querySelectorAll('ui-radio');
        const blurs = [];
        group.addEventListener('blur', () => blurs.push('blur'));
        SCRIPT;

    return browserScript("{$helpers}\n{$body}");
}

test('a control that keeps the focus inside gets a blur when the focus leaves it', function (): void {
    $page = visit('/docs/components/radio');

    expect($page->script(fieldBlurScript(<<<'SCRIPT'
        first.focus();
        second.focus();
        await tick();
        const whileInside = blurs.length;

        second.blur();
        await tick();

        return [whileInside, blurs.length];
        SCRIPT)))->toBe([0, 1]);
});

test('a click inside a control that keeps the focus inside does not count as leaving it', function (): void {
    $page = visit('/docs/components/radio');

    expect($page->script(fieldBlurScript(<<<'SCRIPT'
        first.focus();
        second.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        first.blur();
        await tick();

        return blurs.length;
        SCRIPT)))->toBe(0);
});
