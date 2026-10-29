<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\Tabs\CardTabsHost;

function cardTabsDocument(Testable $component): HTMLDocument
{
    return HTMLDocument::createFromString('<!doctype html>'.$component->html(), LIBXML_NOERROR);
}

test('card tabs default to a strip card with a segmented bar', function (): void {
    $document = cardTabsDocument(Livewire::test(CardTabsHost::class));

    $strip = $document->querySelector('[data-tabs-strip] > [data-flux-card]');

    expect($strip)->not->toBeNull()
        ->and($strip?->classList->contains('rounded-b-none'))->toBeTrue()
        ->and($strip?->classList->contains('border-b-0'))->toBeTrue()
        ->and($strip?->classList->contains('p-2!'))->toBeTrue()
        ->and($strip?->classList->contains('py-0!'))->toBeFalse()
        ->and($document->querySelectorAll('[data-flux-card] [data-flux-tabs] [data-tab]')->length)->toBe(4)
        ->and($strip?->querySelector('[data-flux-tabs]')?->classList->contains('bg-zinc-800/5'))->toBeTrue()
        ->and($document->querySelector('[data-flux-card] [data-flux-navbar]'))->toBeNull();
});

test('a navbar variant puts the navbar in the strip card', function (): void {
    $document = cardTabsDocument(Livewire::test(CardTabsHost::class, ['variant' => 'navbar']));

    $strip = $document->querySelector('[data-tabs-strip] > [data-flux-card]');

    expect($strip?->classList->contains('py-0!'))->toBeTrue()
        ->and($strip?->classList->contains('p-2!'))->toBeFalse()
        ->and($document->querySelectorAll('[data-flux-card] [data-flux-navbar] [data-tab]')->length)->toBe(4)
        ->and($document->querySelector('[data-flux-card] [data-flux-tabs]'))->toBeNull();
});

test('card tabs without a strip render the segmented bar bare', function (): void {
    $document = cardTabsDocument(Livewire::test(CardTabsHost::class, ['strip' => false]));

    expect($document->querySelector('[data-tabs-strip]'))->toBeNull()
        ->and($document->querySelector('[data-flux-card]'))->toBeNull()
        ->and($document->querySelector('[data-flux-tabs]')?->classList->contains('bg-zinc-800/5'))->toBeTrue();
});

test('an underline variant without a strip renders the default bar bare', function (): void {
    $document = cardTabsDocument(Livewire::test(CardTabsHost::class, ['variant' => 'underline', 'strip' => false]));

    $bar = $document->querySelector('[data-flux-tabs]');

    expect($document->querySelector('[data-flux-card]'))->toBeNull()
        ->and($bar)->not->toBeNull()
        ->and($bar?->classList->contains('bg-zinc-800/5'))->toBeFalse();
});

test('another variant goes to the Flux tabs bar', function (): void {
    $document = cardTabsDocument(Livewire::test(CardTabsHost::class, ['variant' => 'pills', 'strip' => false]));

    $bar = $document->querySelector('[data-flux-tabs]');

    expect($bar?->classList->contains('h-8'))->toBeTrue()
        ->and($bar?->classList->contains('border-b'))->toBeFalse();
});

test('the tab content renders outside the strip card in every look', function (array $props): void {
    $document = cardTabsDocument(Livewire::test(CardTabsHost::class, $props));

    expect($document->querySelectorAll('[data-tab-content]')->length)->toBe(1)
        ->and($document->querySelector('[data-flux-card] [data-tab-content]'))->toBeNull()
        ->and($document->querySelector('[data-tab-content] [data-tab-panel="owner"]'))->not->toBeNull();
})->with([
    'strip segmented' => [[]],
    'strip navbar' => [['variant' => 'navbar']],
    'bare segmented' => [['strip' => false]],
    'bare underline' => [['variant' => 'underline', 'strip' => false]],
]);

test('card tabs do not render the title or the description of a tab', function (): void {
    $component = Livewire::test(CardTabsHost::class);

    expect(cardTabsDocument($component)->querySelector('[data-flux-card-heading], [data-flux-card-subheading]'))->toBeNull();
});

test('a strip puts its content directly under the wrapper so the panels can join it', function (): void {
    $document = cardTabsDocument(Livewire::test(CardTabsHost::class));

    expect($document->querySelector('[data-tabs-strip] > [data-tab-content] > [data-tab-panel] > [wire\:id]'))->not->toBeNull();
});

test('card tabs put the attributes of the caller on the wrapper', function (): void {
    $wrapper = cardTabsDocument(Livewire::test(CardTabsHost::class))->querySelector('[data-tabs-strip]');

    expect($wrapper?->getAttribute('id'))->toBe('tabs-test-id')
        ->and($wrapper?->classList->contains('tabs-test-class'))->toBeTrue();
});
