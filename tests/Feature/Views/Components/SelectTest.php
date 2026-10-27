<?php

declare(strict_types=1);

use App\Livewire\Components\Option;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\HtmlString;
use Illuminate\View\ViewException;

test('a select is a searchable listbox that binds to its property and shows its options', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-select label="Country" wire:model.live="country" :options="[\'MY\' => \'Malaysia\', \'SG\' => \'Singapore\']" required />'), LIBXML_NOERROR);
    $select = $document->querySelector('ui-select[data-input-control]');

    expect($select?->getAttribute('wire:model.live'))->toBe('country')
        ->and($select?->hasAttribute('required'))->toBeTrue()
        ->and($select?->hasAttribute('filter'))->toBeTrue()
        ->and($select?->querySelector('[data-flux-select-search] input'))->not->toBeNull()
        ->and(array_map(fn (Element $option): array => [$option->getAttribute('value'), trim($option->textContent)], iterator_to_array($document->querySelectorAll('ui-option[value]'))))
        ->toBe([['MY', 'Malaysia'], ['SG', 'Singapore']]);
});

test('a select has no search input when searchable is off or the variant has none', function (string $template): void {
    $document = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR);

    expect($document->querySelector('[data-input-control]'))->not->toBeNull()
        ->and($document->querySelector('[data-flux-select-search]'))->toBeNull();
})->with([
    'searchable off' => ['<x-select label="Country" :options="[\'MY\' => \'Malaysia\']" :searchable="false" />'],
    'a native select' => ['<x-select label="Country" variant="default" :options="[\'MY\' => \'Malaysia\']" />'],
    'a combobox' => ['<x-select label="Country" variant="combobox" :options="[\'MY\' => \'Malaysia\']" />'],
]);

test('an option object shows its icon, description, badge, and selected label, and can be disabled', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-select label="Plan" :options="$options" />', ['options' => [
        Option::make('pro')->label('Pro')->icon(new HtmlString('<svg data-test="star"></svg>'))->description('Unlimited projects')->badge('Popular')->color('green')->selectedLabel('Pro plan'),
        Option::make('legacy')->label('Legacy')->disabled(),
    ]]), LIBXML_NOERROR);

    $pro = $document->querySelector('ui-option[value="pro"]');

    expect($pro?->getAttribute('selected-label'))->toBe('Pro plan')
        ->and($pro?->querySelector('[data-test="star"]'))->not->toBeNull()
        ->and(trim($pro?->querySelector('[data-flux-text]')?->textContent ?? ''))->toBe('Unlimited projects')
        ->and(trim($pro?->querySelector('[data-flux-badge]')?->textContent ?? ''))->toBe('Popular')
        ->and($pro?->querySelector('[data-flux-badge]')?->getAttribute('class'))->toContain('text-green-800')
        ->and($document->querySelector('ui-option[value="legacy"]')?->hasAttribute('disabled'))->toBeTrue();
});

test('search-model needs the listbox', function (string $variant): void {
    expect(fn () => $this->blade("<x-select label=\"User\" variant=\"{$variant}\" search-model=\"search\" />"))->toThrow(ViewException::class, 'search-model needs the listbox variant');
})->with(['combobox', 'default']);

test('the slot adds options after the options of the options prop', function (): void {
    $view = $this->blade('<x-select label="Project" :options="[1 => \'Website\']"><flux:select.option.create modal="create-project">New project</flux:select.option.create></x-select>');

    $view->assertSeeInOrder(['Website', 'New project']);
});

test('search-model sends the search text to its property and turns off filtering in the browser', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-select label="User" wire:model="user_id" :options="[1 => \'Aisyah\']" search-model="search" search-placeholder="Search users" />'), LIBXML_NOERROR);
    $search = $document->querySelector('[data-flux-select-search] input');

    expect($search?->getAttribute('wire:model.live.debounce.250ms'))->toBe('search')
        ->and($search?->getAttribute('placeholder'))->toBe('Search users')
        ->and($document->querySelector('ui-select')?->hasAttribute('filter'))->toBeFalse();
});

test('a default adds a checkbox named after its option and leaves wire:model on the select', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-select label="State" wire:model.live="state" :options="[10 => \'Selangor\', 14 => \'Kuala Lumpur\']" :default="14" />'), LIBXML_NOERROR);
    $field = $document->querySelector('[data-flux-field]');

    expect([$field?->getAttribute('x-data'), $field?->getAttribute('data-input-default'), $field?->getAttribute('data-input-model')])->toBe(['inputField', '14', 'state'])
        ->and($document->querySelector('ui-select[data-input-control]')?->getAttribute('wire:model.live'))->toBe('state')
        ->and(trim($document->querySelector('ui-checkbox + [data-flux-label]')?->textContent ?? ''))->toBe('Use the default: Kuala Lumpur');
});

test('default-label replaces the label of the default checkbox, which is disabled with the select', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-select label="State" :options="[14 => \'Kuala Lumpur\']" default="14" default-label="Same as the office" disabled />'), LIBXML_NOERROR);

    expect(trim($document->querySelector('ui-checkbox + [data-flux-label]')?->textContent ?? ''))->toBe('Same as the office')
        ->and($document->querySelector('ui-checkbox')?->hasAttribute('disabled'))->toBeTrue();
});

test('a select shows the error of its property and marks its button invalid', function (): void {
    $view = $this->withViewErrors(['country' => 'Choose a country.'])->blade('<x-select label="Country" wire:model="country" :options="[\'MY\' => \'Malaysia\']" />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-select-button]')?->hasAttribute('data-invalid'))->toBeTrue();
    $view->assertSee('Choose a country.');
});

test('a native select shows only the label of an option, so an HTML icon never lands in an attribute', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-select label="Plan" variant="default" :options="$options" />', ['options' => [
        Option::make('team')->label('Team')->icon(new HtmlString('<svg data-test="team-icon" class="size-5"></svg>'))->badge('New'),
    ]]), LIBXML_NOERROR);

    $option = $document->querySelector('select[data-input-control] option[value="team"]');

    expect(trim($option?->textContent ?? ''))->toBe('Team')
        ->and($option?->hasAttribute('icon'))->toBeFalse();
});
