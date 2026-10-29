<?php

declare(strict_types=1);

test('the slider value follows a value that the server sets', function (): void {
    $page = visit('/docs/components/slider');

    expect($page->script(browserScript(<<<'SCRIPT'
        const text = () => control('form.volume').closest('[data-flux-field]').querySelector('[data-input-slider-value]').textContent;

        await setProperty('form.volume', 30);

        return await until(() => text() === '30');
        SCRIPT)))->toBeTrue();
});

test('the slider value follows the user', function (): void {
    $page = visit('/docs/components/slider');

    $page->script(browserScript("await setProperty('form.volume', 30);"));
    $page->keys('[wire\\:model="form.volume"] input[type="range"]', 'ArrowRight');

    expect($page->script(browserScript(<<<'SCRIPT'
        return control('form.volume').closest('[data-flux-field]').querySelector('[data-input-slider-value]').textContent;
        SCRIPT)))->toBe('31');
});

test('a range slider shows both values', function (): void {
    $page = visit('/docs/components/slider');

    expect($page->script(browserScript(<<<'SCRIPT'
        const text = () => control('form.price').closest('[data-flux-field]').querySelector('[data-input-slider-value]').textContent;

        await setProperty('form.price', [100, 500]);

        return await until(() => text() === '100 – 500');
        SCRIPT)))->toBeTrue();
});
