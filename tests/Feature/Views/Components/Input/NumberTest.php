<?php

declare(strict_types=1);

use Dom\Attr;
use Dom\HTMLDocument;

test('wire:model moves to a hidden input after the shown input, which holds the plain number', function (): void {
    $view = $this->blade('<x-input.number label="Amount" wire:model.live.debounce.300ms="amount" />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $shown = $document->querySelector('input[data-input-control]');

    expect($document->querySelector('input[type="hidden"][data-input-number-model]')?->getAttribute('wire:model.live.debounce.300ms'))->toBe('amount')
        ->and(array_filter(array_map(fn (Attr $attribute): string => $attribute->name, iterator_to_array($shown?->attributes ?? [])), fn (string $name): bool => str_starts_with($name, 'wire:model')))->toBeEmpty()
        ->and($document->querySelector('[data-flux-field]')?->getAttribute('data-input-model'))->toBe('amount');
    $view->assertSeeInOrder(['data-input-control', 'data-input-number-model'], false);
});

test('the shown input formats the number with the settings', function (string $template, string $mask, ?string $inputmode): void {
    $document = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR);
    $shown = $document->querySelector('input[data-input-control]');

    expect($shown?->getAttribute('x-mask:dynamic'))->toBe($mask)
        ->and($shown?->getAttribute('inputmode'))->toBe($inputmode);
})->with([
    'the defaults' => ['<x-input.number label="Amount" />', "\$money(\$input, '.', ',', 2)", null],
    'no decimals and no grouping' => ['<x-input.number label="Year" decimals="0" comma="" />', "\$money(\$input, '.', '', 0)", null],
    'other separators' => ['<x-input.number label="Amount" comma="." separator="," />', "\$money(\$input, ',', '.', 2)", null],
    'no negatives' => ['<x-input.number label="Amount" disable-negatives />', "\$money(\$input.replace(/-/g, ''), '.', ',', 2)", 'decimal'],
    'no negatives and no decimals' => ['<x-input.number label="Quantity" decimals="0" disable-negatives />', "\$money(\$input.replace(/-/g, ''), '.', ',', 0)", 'numeric'],
]);

test('the settings reach the number field behavior', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.number label="Amount" decimals="3" comma=" " separator="," disable-negatives />'), LIBXML_NOERROR);
    $field = $document->querySelector('[data-flux-field]');

    expect($field?->getAttribute('x-data'))->toBe('numberField')
        ->and(json_decode((string) $field?->getAttribute('data-number'), true))->toBe(['decimals' => 3, 'comma' => ' ', 'separator' => ',', 'disableNegatives' => true]);
});

test('name and value go to the hidden input, so a plain form posts the plain number', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.number label="Amount" name="amount" value="1234.5" />'), LIBXML_NOERROR);
    $hidden = $document->querySelector('input[data-input-number-model]');

    expect([$hidden?->getAttribute('name'), $hidden?->getAttribute('value')])->toBe(['amount', '1234.5'])
        ->and($document->querySelector('input[data-input-control]')?->hasAttribute('name'))->toBeFalse();
});

test('the shown input is marked invalid and the field shows the error of its property', function (): void {
    $view = $this->withViewErrors(['amount' => 'The amount must be a number.'])->blade('<x-input.number label="Amount" wire:model="amount" />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('input[data-input-control]')?->getAttribute('aria-invalid'))->toBe('true');
    $view->assertSee('The amount must be a number.');
});

test('a text prefix and suffix join the shown input in one group', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.number label="Fuel" prefix="RM" suffix="per litre" />'), LIBXML_NOERROR);

    expect(trim($document->querySelector('[data-flux-input-group] > [data-flux-input-group-prefix]')?->textContent ?? ''))->toBe('RM')
        ->and($document->querySelector('[data-flux-input-group] input[data-input-control]'))->not->toBeNull()
        ->and(trim($document->querySelector('[data-flux-input-group] > [data-flux-input-group-suffix]')?->textContent ?? ''))->toBe('per litre');
});

test('a default adds a checkbox that shows the default with the settings', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.number label="Latitude" wire:model="latitude" decimals="5" :default="3.1234567" />'), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-field]')?->getAttribute('data-input-default'))->toBe('3.1234567')
        ->and(trim($document->querySelector('ui-checkbox + [data-flux-label]')?->textContent ?? ''))->toBe('Use the default: 3.12346');
});
