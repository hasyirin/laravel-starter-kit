<?php

declare(strict_types=1);

test('the text area of the editor has the label of the field as its name', function (): void {
    $page = visit('/docs/components/editor');

    expect($page->script(browserScript(<<<'SCRIPT'
        const textbox = () => control('form.notes').querySelector('.ProseMirror');
        await until(() => textbox()?.hasAttribute('aria-labelledby'));

        return document.getElementById(textbox().getAttribute('aria-labelledby'))?.textContent.trim();
        SCRIPT)))->toBe('Notes');
});
