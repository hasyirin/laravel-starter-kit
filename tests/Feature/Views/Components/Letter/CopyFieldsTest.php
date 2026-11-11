<?php

declare(strict_types=1);

use Dom\Element;
use Dom\HTMLDocument;

test('the add and remove buttons change the rows in the browser', function (): void {
    $view = test()->blade('<x-letter.copy-fields prefix="form.carbon_copies" :$rows />', ['rows' => [
        ['kind' => 'text', 'target' => null, 'text' => 'Finance'],
        ['kind' => 'text', 'target' => null, 'text' => 'Registry'],
    ]]);
    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    $rows = $document->querySelector('[data-row-add]')?->closest('[x-data]')?->getAttribute('x-data');
    $add = $document->querySelector('[data-row-add]')?->getAttribute('x-on:click');
    $removes = collect($document->querySelectorAll('[data-row-remove]'))->map(fn (Element $button): ?string => $button->getAttribute('x-on:click'))->all();

    expect($rows)->toBe("letterRows('form.carbon_copies')")
        ->and($add)->toContain('addRow')
        ->and($removes)->toBe(['removeRow(0)', 'removeRow(1)']);
});
