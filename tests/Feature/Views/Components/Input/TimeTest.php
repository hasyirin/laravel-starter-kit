<?php

declare(strict_types=1);

use Dom\HTMLDocument;

test('a time is a typed input in the app locale', function (): void {
    config(['system.locale' => 'en-GB']);

    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.time label="Opens at" wire:model="opens_at" />'), LIBXML_NOERROR);

    expect($document->querySelector('ui-time-picker[data-input-control]')?->getAttribute('locale'))->toBe('en-GB')
        ->and($document->querySelector('ui-time-picker [data-flux-hour-input]'))->not->toBeNull();
});

test('the type and locale of a time can be replaced', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.time label="Opens at" type="button" locale="fr" />'), LIBXML_NOERROR);

    expect($document->querySelector('ui-time-picker')?->getAttribute('locale'))->toBe('fr')
        ->and($document->querySelector('ui-time-picker [data-flux-time-picker-button]'))->not->toBeNull()
        ->and($document->querySelector('ui-time-picker [data-flux-hour-input]'))->toBeNull();
});

test('a time binds to its property, is required, and shows the error of its property', function (): void {
    $view = $this->withViewErrors(['opens_at' => 'The opening time is required.'])->blade('<x-input.time label="Opens at" wire:model="opens_at" required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $picker = $document->querySelector('ui-time-picker');

    expect($picker?->getAttribute('wire:model.self'))->toBe('opens_at')
        ->and($picker?->hasAttribute('required'))->toBeTrue();
    $view->assertSee('The opening time is required.');
});
