<?php

declare(strict_types=1);

test('the datetime input shows the property in the time zone of the user', function (): void {
    $page = visit('/docs/components/input.datetime');

    expect($page->script(browserScript(<<<'SCRIPT'
        await setProperty('form.starts_at', '2026-03-01 01:00:00');
        await until(() => control('form.starts_at').value === '2026-03-01T09:00');

        return control('form.starts_at').value;
        SCRIPT)))->toBe('2026-03-01T09:00');
});

test('the datetime input sends the picked time in the app time zone', function (): void {
    $page = visit('/docs/components/input.datetime');

    expect($page->script(browserScript(<<<'SCRIPT'
        const picker = control('form.starts_at');

        picker.value = '2026-03-01T09:00';
        picker.dispatchEvent(new Event('input'));
        await tick();

        return property('form.starts_at');
        SCRIPT)))->toBe('2026-03-01T01:00');
});

test('a datetime range sends both ends in the app time zone', function (): void {
    $page = visit('/docs/components/input.datetime');

    expect($page->script(browserScript(<<<'SCRIPT'
        const picker = control('form.maintenance_window');

        picker.value = { start: '2026-03-01T08:00', end: '2026-03-02T07:30' };
        picker.dispatchEvent(new Event('input'));
        await tick();

        return property('form.maintenance_window');
        SCRIPT)))->toBe(['start' => '2026-03-01T00:00', 'end' => '2026-03-01T23:30']);
});
