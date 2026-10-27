<?php

declare(strict_types=1);

use App\Livewire\Components\Option;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\HtmlString;

test('without options, a checkbox is one checkbox with its label beside it and the asterisk outside the label', function (): void {
    $view = $this->withViewErrors(['terms' => 'Accept the terms to continue.'])->blade('<x-checkbox label="I accept the terms" description="We keep a copy of your answer." wire:model="terms" required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $checkbox = $document->querySelector('[data-flux-field] > ui-checkbox[data-input-control]');

    expect($checkbox?->getAttribute('wire:model'))->toBe('terms')
        ->and($checkbox?->hasAttribute('required'))->toBeTrue()
        ->and($document->querySelector('ui-checkbox + [data-input-label] > span[aria-hidden="true"]')?->textContent)->toBe('*')
        ->and($document->querySelector('[data-flux-label] span[aria-hidden="true"]'))->toBeNull()
        ->and($document->querySelector('ui-checkbox-group'))->toBeNull();
    $view->assertSeeInOrder(['I accept the terms', 'We keep a copy of your answer.', 'Accept the terms to continue.']);
});

test('with options, even none, or with checkboxes in the slot, a checkbox is a group under a label row', function (string $template, array $values): void {
    $document = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR);
    $group = $document->querySelector('[data-input-label-row] ~ ui-checkbox-group[data-input-control]');

    expect($group?->getAttribute('wire:model.self'))->toBe('days')
        ->and(array_map(fn (Element $checkbox): ?string => $checkbox->getAttribute('value'), iterator_to_array($group?->querySelectorAll('ui-checkbox[value]') ?? [])))->toBe($values);
})->with([
    'options' => ['<x-checkbox label="Days" wire:model="days" :options="[\'mon\' => \'Monday\', \'tue\' => \'Tuesday\']" />', ['mon', 'tue']],
    'no options' => ['<x-checkbox label="Days" wire:model="days" :options="[]" />', []],
    'checkboxes in the slot' => ['<x-checkbox label="Days" wire:model="days"><flux:checkbox value="sat" label="Saturday" /></x-checkbox>', ['sat']],
]);

test('the checkboxes of a group carry a key and the description, icon, and disabled state of their option', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-checkbox label="Plans" variant="cards" :options="$options" />', ['options' => [
        Option::make('pro')->label('Pro')->description('Unlimited projects')->icon(new HtmlString('<svg data-test="star"></svg>')),
        Option::make('legacy')->label('Legacy')->disabled(),
    ]]), LIBXML_NOERROR);

    $pro = $document->querySelector('ui-checkbox[data-flux-checkbox-cards][value="pro"]');

    expect($pro?->getAttribute('wire:key'))->toBe('option-pro')
        ->and($pro?->textContent)->toContain('Unlimited projects')
        ->and($pro?->querySelector('[data-test="star"]'))->not->toBeNull()
        ->and($document->querySelector('ui-checkbox[value="legacy"]')?->hasAttribute('disabled'))->toBeTrue();
});

test('all adds a checkbox for every option before the options', function (string $template, string $label): void {
    $first = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR)->querySelector('ui-checkbox-group ui-checkbox');

    expect($first?->hasAttribute('all'))->toBeTrue()
        ->and(trim($first?->nextElementSibling?->textContent ?? ''))->toBe($label);
})->with([
    'the default label' => ['<x-checkbox label="Days" :options="[\'mon\' => \'Monday\']" all />', 'Select all'],
    'its own label' => ['<x-checkbox label="Days" :options="[\'mon\' => \'Monday\']" all="Every day" />', 'Every day'],
]);

test('a group shows the errors of its items', function (): void {
    $view = $this->withViewErrors(['days.0' => 'The selected day is invalid.'])->blade('<x-checkbox label="Days" wire:model="days" :options="[\'mon\' => \'Monday\']" />');

    $view->assertSee('The selected day is invalid.');
});

test('the default variant leaves out the icon of an option and shows an HTML label as text, so no HTML lands in an attribute', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-checkbox label="Plans" :options="$options" />', ['options' => [
        Option::make('team')->label(new HtmlString('<span class="font-bold">Team</span> plan'))->icon(new HtmlString('<svg data-test="team-icon" class="size-5"></svg>')),
        Option::make('solo')->label('Solo'),
    ]]), LIBXML_NOERROR);

    expect($document->querySelector('ui-checkbox[value="team"]')?->hasAttribute('icon'))->toBeFalse()
        ->and($document->querySelector('ui-checkbox[value="team"]')?->getAttribute('label'))->toBe('Team plan')
        ->and($document->querySelector('[data-test="team-icon"]'))->toBeNull()
        ->and($document->querySelector('ui-checkbox[value="solo"]'))->not->toBeNull();
});
