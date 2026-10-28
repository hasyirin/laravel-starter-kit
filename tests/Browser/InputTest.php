<?php

declare(strict_types=1);

test('an input with a case changes the text as the user types', function (string $model, string $typed, string $shown): void {
    $page = visit('/docs/components/input');

    $page->type("[name=\"{$model}\"]", $typed);

    expect($page->value("[name=\"{$model}\"]"))->toBe($shown)
        ->and($page->script(browserScript("return property('{$model}');")))->toBe($shown);
})->with([
    'upper' => ['form.upper', 'my-001', 'MY-001'],
    'lower' => ['form.lower', 'Ali@Example.COM', 'ali@example.com'],
    'title' => ['form.title', 'jean-luc PICARD', 'Jean-Luc Picard'],
    'sentence' => ['form.sentence', 'hello. WORLD', 'Hello. World'],
]);

test('the default checkbox fills the default and gives back what the user typed', function (): void {
    $page = visit('/docs/components/input');

    $page->type('[name="form.city"]', 'Ipoh');

    expect($page->script(browserScript(<<<'SCRIPT'
        const checkbox = control('form.city').closest('[data-flux-field]').querySelector('ui-checkbox');
        const states = [];

        checkbox.click();
        await tick();
        states.push([control('form.city').value, property('form.city')]);

        checkbox.click();
        await tick();
        states.push([control('form.city').value, property('form.city')]);

        return states;
        SCRIPT)))->toBe([['Kuala Lumpur', 'Kuala Lumpur'], ['Ipoh', 'Ipoh']]);
});

test('the default checkbox follows a value that the server sets', function (): void {
    $page = visit('/docs/components/input');

    expect($page->script(browserScript(<<<'SCRIPT'
        const checkbox = control('form.city').closest('[data-flux-field]').querySelector('ui-checkbox');

        await setProperty('form.city', 'Kuala Lumpur');
        const checked = await until(() => checkbox.hasAttribute('checked') || checkbox.checked === true);

        await setProperty('form.city', 'Ipoh');
        const unchecked = await until(() => ! checkbox.hasAttribute('checked') && checkbox.checked !== true);

        return checked && unchecked && control('form.city').value === 'Ipoh';
        SCRIPT)))->toBeTrue();
});
