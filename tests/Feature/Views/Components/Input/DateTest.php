<?php

declare(strict_types=1);

use Dom\Element;
use Dom\HTMLDocument;

test('a date opens from a typed input, with the today button, the month and year menus, and the app locale', function (): void {
    config(['system.locale' => 'en-GB']);

    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.date label="Starts on" wire:model="starts_on" />'), LIBXML_NOERROR);

    expect($document->querySelector('ui-date-picker[data-input-control]')?->getAttribute('locale'))->toBe('en-GB')
        ->and($document->querySelector('ui-date-picker [data-flux-day-input]'))->not->toBeNull()
        ->and($document->querySelector('ui-date-picker ui-calendar-today'))->not->toBeNull()
        ->and($document->querySelector('ui-date-picker ui-calendar-month select'))->not->toBeNull();
});

test('each default of a date can be replaced', function (): void {
    $view = $this->blade('<x-input.date label="Starts on" type="button" :with-today="false" :selectable-header="false" locale="fr" />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('ui-date-picker')?->getAttribute('locale'))->toBe('fr')
        ->and($document->querySelector('ui-date-picker [data-flux-date-picker-button]'))->not->toBeNull()
        ->and($document->querySelector('ui-date-picker [data-flux-day-input]'))->toBeNull()
        ->and($document->querySelector('ui-date-picker ui-calendar-today'))->toBeNull()
        ->and($document->querySelector('ui-date-picker ui-calendar-month'))->toBeNull();
});

test('a date range opens from the button, because the typed input holds one date', function (string $template, bool $typed): void {
    $document = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR);

    expect($document->querySelector('ui-date-picker [data-flux-day-input]') instanceof Element)->toBe($typed);
})->with([
    'a range' => ['<x-input.date label="Leave" mode="range" />', false],
    'a range with a typed input' => ['<x-input.date label="Leave" mode="range" type="input" />', true],
]);

test('a trigger slot replaces the input of a date', function (): void {
    $view = $this->blade('<x-input.date label="Starts on"><x-slot:trigger><button type="button" data-test="trigger">Pick</button></x-slot:trigger></x-input.date>');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('ui-date-picker [data-test="trigger"]'))->not->toBeNull()
        ->and($document->querySelector('ui-date-picker [data-flux-day-input]'))->toBeNull();
});

test('a date binds to its property, is required, and shows the error of its property', function (): void {
    $view = $this->withViewErrors(['starts_on' => 'The start date is required.'])->blade('<x-input.date label="Starts on" wire:model="starts_on" required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $picker = $document->querySelector('ui-date-picker');

    expect($picker?->getAttribute('wire:model.self'))->toBe('starts_on')
        ->and($picker?->hasAttribute('required'))->toBeTrue()
        ->and($document->querySelector('[data-input-label] > span[aria-hidden="true"]')?->textContent)->toBe('*');
    $view->assertSee('The start date is required.');
});
