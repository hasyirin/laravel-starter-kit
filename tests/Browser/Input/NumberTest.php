<?php

declare(strict_types=1);

const NUMBER_PRICE = '[data-input-model="form.price"] [data-input-control]';

test('the number input shows the separators and sends the plain number', function (): void {
    $page = visit('/docs/components/input.number');

    $page->type(NUMBER_PRICE, '1234,5');

    expect($page->value(NUMBER_PRICE))->toBe('1 234,5')
        ->and($page->script(browserScript("return property('form.price');")))->toBe('1234.5');
});

test('the number input shows a value from the server with its separators and decimals', function (): void {
    $page = visit('/docs/components/input.number');

    expect($page->script(browserScript(<<<'SCRIPT'
        await setProperty('form.price', '9876.5');
        await until(() => control('form.price').value === '9 876,50');

        return control('form.price').value;
        SCRIPT)))->toBe('9 876,50');
});

test('the number input keeps an untouched value that has more decimals than it shows', function (): void {
    $page = visit('/docs/components/input.number');

    expect($page->script(browserScript(<<<'SCRIPT'
        await setProperty('form.latitude', '1.123456');
        await until(() => control('form.latitude').value === '1.12346');

        control('form.latitude').focus();
        control('form.latitude').blur();
        await tick();

        return [control('form.latitude').value, property('form.latitude')];
        SCRIPT)))->toBe(['1.12346', '1.123456']);
});

test('a number input that disables negatives drops the minus sign', function (): void {
    $page = visit('/docs/components/input.number');

    $page->type('[data-input-model="form.weight"] [data-input-control]', '-12');

    expect($page->script(browserScript("return property('form.weight');")))->toBe('12');
});

test('the default checkbox of a number input fills the default with the decimals of the field', function (): void {
    $page = visit('/docs/components/input.number');

    expect($page->script(browserScript(<<<'SCRIPT'
        control('form.home_latitude').closest('[data-flux-field]').querySelector('ui-checkbox').click();
        await tick();

        return [control('form.home_latitude').value, property('form.home_latitude')];
        SCRIPT)))->toBe(['3.13900', '3.13900']);
});
