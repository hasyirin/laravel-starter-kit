<?php

declare(strict_types=1);

use Dom\HTMLDocument;

test('a date and time has the today button, the month and year menus, and the app locale', function (): void {
    config(['system.locale' => 'en-GB']);

    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.datetime label="Starts at" wire:model="starts_at" />'), LIBXML_NOERROR);

    expect($document->querySelector('ui-datetime-picker[data-input-control]')?->getAttribute('locale'))->toBe('en-GB')
        ->and($document->querySelector('ui-datetime-picker ui-calendar-today'))->not->toBeNull()
        ->and($document->querySelector('ui-datetime-picker ui-calendar-month select'))->not->toBeNull();
});

test('each default of a date and time can be replaced', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.datetime label="Starts at" :with-today="false" :selectable-header="false" locale="fr" />'), LIBXML_NOERROR);

    expect($document->querySelector('ui-datetime-picker')?->getAttribute('locale'))->toBe('fr')
        ->and($document->querySelector('ui-datetime-picker ui-calendar-today'))->toBeNull()
        ->and($document->querySelector('ui-datetime-picker ui-calendar-month'))->toBeNull();
});

test('a trigger slot replaces the button of a date and time', function (): void {
    $view = $this->blade('<x-input.datetime label="Send at"><x-slot:trigger><button type="button" data-test="trigger">Schedule</button></x-slot:trigger></x-input.datetime>');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('ui-datetime-picker [data-test="trigger"]'))->not->toBeNull()
        ->and($document->querySelector('ui-datetime-picker [data-flux-datetime-picker-button]'))->toBeNull();
});

test('a date and time binds to its property, is required, and shows the error of its property', function (): void {
    $view = $this->withViewErrors(['starts_at' => 'The start time is required.'])->blade('<x-input.datetime label="Starts at" wire:model="starts_at" required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $picker = $document->querySelector('ui-datetime-picker');

    expect($picker?->getAttribute('wire:model.self'))->toBe('starts_at')
        ->and($picker?->hasAttribute('required'))->toBeTrue();
    $view->assertSee('The start time is required.');
});
