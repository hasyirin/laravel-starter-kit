<?php

declare(strict_types=1);

use Dom\HTMLDocument;

test('a slider binds to its property, shows its value at the end of the label row, and shows the error of its property', function (): void {
    $view = $this->withViewErrors(['volume' => 'The volume must be at least 10.'])->blade('<x-slider label="Volume" wire:model="volume" min="0" max="100" step="5" />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $slider = $document->querySelector('ui-slider[data-input-control]');

    expect($slider?->getAttribute('wire:model'))->toBe('volume')
        ->and($slider?->querySelector('input[type="range"]')?->getAttribute('step'))->toBe('5')
        ->and($document->querySelector('[data-flux-field]')?->getAttribute('x-data'))->toBe('sliderField')
        ->and($document->querySelector('[data-input-hint] [data-input-slider-value]')?->getAttribute('x-text'))->toBe('text');
    $view->assertSee('The volume must be at least 10.');
});

test('a hint takes the place of the value, and show-value off leaves it out', function (string $template, ?string $hint): void {
    $document = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR);

    expect($document->querySelector('[data-input-slider-value]'))->toBeNull()
        ->and($document->querySelector('[data-flux-field]')?->hasAttribute('x-data'))->toBeFalse()
        ->and($document->querySelector('[data-input-hint]')?->textContent)->toBe($hint);
})->with([
    'a hint' => ['<x-slider label="Volume" hint="dB" wire:model="volume" />', 'dB'],
    'show-value off' => ['<x-slider label="Volume" :show-value="false" wire:model="volume" />', null],
]);

test('a range slider has a thumb for each end', function (): void {
    $slider = HTMLDocument::createFromString((string) $this->blade('<x-slider label="Price" wire:model="price" range />'), LIBXML_NOERROR)->querySelector('ui-slider');

    expect($slider?->hasAttribute('range'))->toBeTrue()
        ->and($slider?->querySelectorAll('[data-flux-slider-thumb]'))->toHaveCount(2);
});
