<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Dom\Attr;
use Dom\Element;
use Dom\HTMLDocument;

test('a date and time has the today button, the month and year menus, and the locale of the user', function (): void {
    context()->addHidden('locale', 'en-GB');

    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.datetime label="Starts at" wire:model="starts_at" />'), LIBXML_NOERROR);

    expect($document->querySelector('ui-datetime-picker[data-input-control]')?->getAttribute('locale'))->toBe('en-GB')
        ->and($document->querySelector('ui-datetime-picker ui-calendar-today'))->not->toBeNull()
        ->and($document->querySelector('ui-datetime-picker ui-calendar-month select'))->not->toBeNull();
});

test('the user types the time by default, and time="list" picks it from a list', function (string $template, bool $typed): void {
    $document = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR);

    expect($document->querySelector('ui-datetime-picker [data-flux-hour-input]') instanceof Element)->toBe($typed)
        ->and($document->querySelector('ui-datetime-picker [data-flux-datetime-picker-times]') instanceof Element)->toBe(! $typed);
})->with([
    'the default' => ['<x-input.datetime label="Starts at" />', true],
    'a list' => ['<x-input.datetime label="Starts at" time="list" />', false],
]);

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

test('wire:model sits on a hidden element in the app time zone, whatever it is, and the picker converts with the zone of the user', function (): void {
    config(['app.timezone' => 'Asia/Kuala_Lumpur']);
    context()->addHidden('timezone', 'Asia/Tokyo');

    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.datetime label="Starts at" wire:model.live="starts_at" />'), LIBXML_NOERROR);
    $field = $document->querySelector('[data-flux-field]');
    $picker = $document->querySelector('ui-datetime-picker');

    expect($document->querySelector('[data-input-datetime-model][hidden]')?->getAttribute('wire:model.live'))->toBe('starts_at')
        ->and(array_filter(array_map(fn (Attr $attribute): string => $attribute->name, iterator_to_array($picker?->attributes ?? [])), fn (string $name): bool => str_starts_with($name, 'wire:model')))->toBeEmpty()
        ->and([$field?->getAttribute('x-data'), $field?->getAttribute('data-timezone'), $field?->getAttribute('data-app-timezone'), $field?->getAttribute('data-input-model')])
        ->toBe(['datetimeField', 'Asia/Tokyo', 'Asia/Kuala_Lumpur', 'starts_at']);
});

test('without wire:model, the picker works like flux:datetime-picker, in the time zone of the user', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.datetime label="From" name="from" value="2026-10-26T14:30" />'), LIBXML_NOERROR);
    $picker = $document->querySelector('ui-datetime-picker');

    expect($document->querySelector('[data-flux-field]')?->hasAttribute('x-data'))->toBeFalse()
        ->and($document->querySelector('[data-input-datetime-model]'))->toBeNull()
        ->and([$picker?->getAttribute('name'), $picker?->getAttribute('value')])->toBe(['from', '2026-10-26T14:30']);
});

test('limits given as instants, now, or today move to the zone of the user', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 20:00', 'UTC'));
    context()->addHidden('timezone', 'Asia/Kuala_Lumpur');

    $view = $this->blade('<x-input.datetime label="Starts at" :min="$min" max="today" open-to="now" :unavailable="$unavailable" />', [
        'min' => CarbonImmutable::parse('2026-10-26 06:30', 'UTC'),
        'unavailable' => [CarbonImmutable::parse('2026-10-28 01:00', 'UTC'), '12:00-13:00'],
    ]);

    $picker = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR)->querySelector('ui-datetime-picker');

    expect([$picker?->getAttribute('min'), $picker?->getAttribute('max'), $picker?->getAttribute('open-to'), $picker?->getAttribute('unavailable')])
        ->toBe(['2026-10-26T14:30', '2026-10-27', '2026-10-27T04:00', '2026-10-28T09:00,12:00-13:00']);
});

test('a date and time is required, and an error of its property marks it invalid and shows the message', function (): void {
    $view = $this->withViewErrors(['starts_at' => 'The start time is required.'])->blade('<x-input.datetime label="Starts at" wire:model="starts_at" required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('ui-datetime-picker')?->hasAttribute('required'))->toBeTrue()
        ->and($document->querySelector('ui-datetime-picker [data-flux-datetime-picker-button]')?->hasAttribute('data-invalid'))->toBeTrue();
    $view->assertSee('The start time is required.');
});

test('the invalid attribute decides over the errors of the property', function (string $template, array $errors, bool $invalid): void {
    $view = $this->withViewErrors($errors)->blade($template);

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('ui-datetime-picker [data-flux-datetime-picker-button]')?->hasAttribute('data-invalid'))->toBe($invalid);
})->with([
    'invalid without an error' => ['<x-input.datetime label="Starts at" wire:model="starts_at" invalid />', [], true],
    ':invalid="false" with an error' => ['<x-input.datetime label="Starts at" wire:model="starts_at" :invalid="false" />', ['starts_at' => 'The start time is required.'], false],
]);
