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

    expect($rows)->toBe("repeatableRows('form.carbon_copies')")
        ->and($add)->toContain('addRow')
        ->and($removes)->toBe(['removeRow(0)', 'removeRow(1)']);
});

test('the error of a carbon copy row shows one time, on its row', function (): void {
    $view = test()->withViewErrors(['form.carbon_copies.0.text' => 'The carbon copy field is required.'])->blade('<x-letter.copy-fields prefix="form.carbon_copies" :$rows />', ['rows' => [
        ['kind' => 'text', 'target' => null, 'text' => ''],
        ['kind' => 'text', 'target' => null, 'text' => 'Registry'],
    ]]);
    $rowField = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR)->querySelector('input[wire\:model="form.carbon_copies.0.text"]')?->closest('[data-flux-field]');

    expect(substr_count((string) $view, 'The carbon copy field is required.'))->toBe(1)
        ->and($rowField?->textContent)->toContain('The carbon copy field is required.');
});

test('the error of the carbon copy list shows one time, under the rows', function (): void {
    $view = test()->withViewErrors(['form.carbon_copies' => 'The carbon copies field must be an array.'])->blade('<x-letter.copy-fields prefix="form.carbon_copies" :$rows />', ['rows' => [
        ['kind' => 'text', 'target' => null, 'text' => 'Finance'],
    ]]);

    expect(substr_count((string) $view, 'The carbon copies field must be an array.'))->toBe(1);
    $view->assertSeeInOrder(['data-row-add', 'The carbon copies field must be an array.'], false);
});
