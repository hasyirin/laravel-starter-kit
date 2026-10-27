<?php

declare(strict_types=1);

use App\Livewire\Components\Option;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\HtmlString;
use Illuminate\View\ViewException;

test('a pillbox takes many values, is searchable, binds to its property, shows its options, and shows the error of its property', function (): void {
    $view = $this->withViewErrors(['skills' => 'Choose at least one skill.'])->blade('<x-pillbox label="Skills" wire:model="skills" :options="[\'php\' => \'PHP\', \'js\' => \'JavaScript\']" />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $pillbox = $document->querySelector('ui-pillbox[data-input-control]');

    expect($pillbox?->getAttribute('wire:model'))->toBe('skills')
        ->and($pillbox?->hasAttribute('multiple'))->toBeTrue()
        ->and($pillbox?->querySelector('[data-flux-pillbox-search] input'))->not->toBeNull()
        ->and(array_map(fn (Element $option): array => [$option->getAttribute('value'), trim($option->textContent)], iterator_to_array($document->querySelectorAll('ui-option[value]'))))
        ->toBe([['php', 'PHP'], ['js', 'JavaScript']]);
    $view->assertSee('Choose at least one skill.');
});

test('a rich option names its pill with its label', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-pillbox label="Plans" :options="$options" />', ['options' => [
        Option::make('pro')->label('Pro')->icon(new HtmlString('<svg data-test="star"></svg>'))->description('Unlimited projects')->badge('Popular'),
        Option::make('free')->label('Free'),
        Option::make('team')->label(new HtmlString('<span class="font-bold">Team</span> plan'))->description('Five users'),
    ]]), LIBXML_NOERROR);

    $pro = $document->querySelector('ui-option[value="pro"]');

    expect($pro?->getAttribute('selected-label'))->toBe('Pro')
        ->and($pro?->querySelector('[data-test="star"]'))->not->toBeNull()
        ->and(trim($pro?->querySelector('[data-flux-text]')?->textContent ?? ''))->toBe('Unlimited projects')
        ->and(trim($pro?->querySelector('[data-flux-badge]')?->textContent ?? ''))->toBe('Popular')
        ->and($document->querySelector('ui-option[value="free"]')?->hasAttribute('selected-label'))->toBeFalse()
        ->and($document->querySelector('ui-option[value="team"]')?->getAttribute('selected-label'))->toBe('Team plan');
});

test('search-model needs the default variant', function (): void {
    expect(fn () => $this->blade('<x-pillbox label="Tags" variant="combobox" search-model="search" />'))->toThrow(ViewException::class, 'search-model needs the default variant');
});

test('search-model sends the search text to its property and turns off filtering in the browser', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-pillbox label="Tags" wire:model="tags" :options="[\'urgent\' => \'Urgent\']" search-model="search" search-placeholder="Search tags" />'), LIBXML_NOERROR);
    $search = $document->querySelector('[data-flux-pillbox-search] input');

    expect($search?->getAttribute('wire:model.live.debounce.250ms'))->toBe('search')
        ->and($search?->getAttribute('placeholder'))->toBe('Search tags')
        ->and($document->querySelector('ui-pillbox')?->hasAttribute('filter'))->toBeFalse();
});
