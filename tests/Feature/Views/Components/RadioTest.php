<?php

declare(strict_types=1);

use App\Livewire\Components\Option;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\HtmlString;

test('a radio group binds to its property, shows its options with a key, and shows the error of its property', function (): void {
    $view = $this->withViewErrors(['carrier' => 'Choose a carrier.'])->blade('<x-radio label="Carrier" wire:model.live="carrier" :options="[\'road\' => \'Road\', \'sea\' => \'Sea\']" required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $group = $document->querySelector('[data-input-label-row] ~ ui-radio-group[data-input-control]');

    expect($group?->getAttribute('wire:model.live'))->toBe('carrier')
        ->and($document->querySelector('[data-input-label] > span[aria-hidden="true"]')?->textContent)->toBe('*')
        ->and(array_map(fn (Element $radio): array => [$radio->getAttribute('value'), $radio->getAttribute('wire:key')], iterator_to_array($document->querySelectorAll('ui-radio'))))
        ->toBe([['road', 'option-road'], ['sea', 'option-sea']]);
    $view->assertSee('Choose a carrier.');
});

test('the variant and size reach the radios, which show the description, icon, and disabled state of their option', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-radio label="Plan" variant="cards" :options="$options" />', ['options' => [
        Option::make('pro')->label('Pro')->description('Unlimited projects')->icon(new HtmlString('<svg data-test="star"></svg>')),
        Option::make('legacy')->label('Legacy')->disabled(),
    ]]), LIBXML_NOERROR);

    $pro = $document->querySelector('ui-radio[data-flux-radio-cards][value="pro"]');

    expect($pro?->textContent)->toContain('Unlimited projects')
        ->and($pro?->querySelector('[data-test="star"]'))->not->toBeNull()
        ->and($document->querySelector('ui-radio[value="legacy"]')?->hasAttribute('disabled'))->toBeTrue();

    $segmented = HTMLDocument::createFromString((string) $this->blade('<x-radio label="Type" variant="segmented" size="sm" :options="[\'a\' => \'A\']" />'), LIBXML_NOERROR);

    expect($segmented->querySelector('ui-radio-group[data-flux-radio-group-segmented]')?->getAttribute('class'))->toContain('h-8');
});

test('the default variant leaves out the icon of an option and shows an HTML label as text, so no HTML lands in an attribute', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-radio label="Plan" :options="$options" />', ['options' => [
        Option::make('team')->label(new HtmlString('<span class="font-bold">Team</span> plan'))->icon(new HtmlString('<svg data-test="team-icon" class="size-5"></svg>')),
        Option::make('solo')->label('Solo'),
    ]]), LIBXML_NOERROR);

    expect($document->querySelector('ui-radio[value="team"]')?->hasAttribute('icon'))->toBeFalse()
        ->and($document->querySelector('ui-radio[value="team"]')?->getAttribute('label'))->toBe('Team plan')
        ->and($document->querySelector('[data-test="team-icon"]'))->toBeNull()
        ->and($document->querySelector('ui-radio[value="solo"]'))->not->toBeNull();
});
