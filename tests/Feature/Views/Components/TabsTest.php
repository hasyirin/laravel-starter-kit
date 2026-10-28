<?php

declare(strict_types=1);

use App\Livewire\Components\Tab;
use Dom\HTMLDocument;

beforeEach(function (): void {
    $this->tabs = [
        Tab::make('profile')->label('Profile')->icon('user')->url('https://example.test/settings?search=bob')->markDefault(),
        Tab::make('billing')->label('Billing')->badge('3')->badgeColor('red')->tooltip('Invoices and plans')->url('https://example.test/settings?search=bob&tab=billing'),
        Tab::make('audit')->label('Audit')->disabled(true)->tooltip('Shown to all', disabled: 'Ask an admin')->url('https://example.test/settings?search=bob&tab=audit'),
    ];
});

test('a tab bar renders one item per tab with its label, its href, and its slug', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-tabs :tabs="$tabs" :current-tab="$tabs[0]" aria-label="Settings" />', ['tabs' => $this->tabs]), LIBXML_NOERROR);

    $items = $document->querySelectorAll('[data-flux-tabs] [data-flux-tab][data-tab]');

    expect($items->length)->toBe(3)
        ->and(array_map(fn ($item): string => (string) str($item->textContent)->squish(), iterator_to_array($items)))->toBe(['Profile', 'Billing 3', 'Audit'])
        ->and($document->querySelector('[data-tab="profile"]')?->getAttribute('href'))->toBe('https://example.test/settings?search=bob')
        ->and($document->querySelector('[data-tab="billing"]')?->getAttribute('href'))->toBe('https://example.test/settings?search=bob&tab=billing')
        ->and($document->querySelector('[data-flux-tabs]')?->getAttribute('aria-label'))->toBe('Settings')
        ->and($document->querySelector('[data-flux-tabs]')?->getAttribute('x-data'))->toBe("tabs('tab', 'profile')");
});

test('a tab bar shows the icon and the badge of a tab', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-tabs :tabs="$tabs" :current-tab="$tabs[0]" />', ['tabs' => $this->tabs]), LIBXML_NOERROR);

    $badge = $document->querySelector('[data-tab="billing"] [data-flux-badge]');

    expect($document->querySelector('[data-tab="profile"] svg'))->not->toBeNull()
        ->and(trim($badge?->textContent ?? ''))->toBe('3')
        ->and($badge?->getAttribute('class'))->toContain('red');
});

test('a tab bar marks the current tab only', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-tabs :tabs="$tabs" :current-tab="$tabs[1]" />', ['tabs' => $this->tabs]), LIBXML_NOERROR);

    expect($document->querySelector('[data-tab="billing"]')?->hasAttribute('data-selected'))->toBeTrue()
        ->and($document->querySelectorAll('[data-tab][data-selected]')->length)->toBe(1)
        ->and(array_map(fn ($item): ?string => $item->getAttribute('aria-selected'), iterator_to_array($document->querySelectorAll('[data-tab]'))))->toBe(['false', 'true', 'false']);
});

test('a disabled tab in a tab bar has no href and is greyed out', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-tabs :tabs="$tabs" :current-tab="$tabs[0]" />', ['tabs' => $this->tabs]), LIBXML_NOERROR);

    $audit = $document->querySelector('[data-tab="audit"]');

    expect($audit?->hasAttribute('href'))->toBeFalse()
        ->and($audit?->localName)->toBe('div')
        ->and($audit?->getAttribute('aria-disabled'))->toBe('true')
        ->and($audit?->classList->contains('opacity-50'))->toBeTrue()
        ->and($audit?->classList->contains('cursor-default'))->toBeTrue()
        ->and($audit?->classList->contains('pointer-events-none'))->toBeFalse()
        ->and($document->querySelector('[data-tab="profile"]')?->classList->contains('opacity-50'))->toBeFalse();
});

test('a tooltip wraps a tab in a tab bar only when the tab has tooltip text, and a disabled tab shows its disabled text', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-tabs :tabs="$tabs" :current-tab="$tabs[0]" />', ['tabs' => $this->tabs]), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-tooltip] > [data-tab="profile"]'))->toBeNull()
        ->and(trim($document->querySelector('[data-flux-tooltip]:has(> [data-tab="billing"]) [data-flux-tooltip-content]')?->textContent ?? ''))->toBe('Invoices and plans')
        ->and(trim($document->querySelector('[data-flux-tooltip]:has(> [data-tab="audit"]) [data-flux-tooltip-content]')?->textContent ?? ''))->toBe('Ask an admin');
});

test('the default slug of a tab bar comes from the tab marked as default, and the query key reaches the Alpine behavior', function (): void {
    $tabs = [
        Tab::make('audit')->url('https://example.test/settings?section=audit'),
        Tab::make('profile')->url('https://example.test/settings')->markDefault(),
    ];

    $document = HTMLDocument::createFromString((string) $this->blade('<x-tabs :tabs="$tabs" :current-tab="$tabs[1]" query-as="section" />', ['tabs' => $tabs]), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-tabs]')?->getAttribute('x-data'))->toBe("tabs('section', 'profile')");
});

test('the key of each tab in a tab bar sits on the direct child of the loop', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-tabs :tabs="$tabs" :current-tab="$tabs[0]" />', ['tabs' => $this->tabs]), LIBXML_NOERROR);

    $billing = $document->querySelector('[data-tab="billing"]');

    expect($document->querySelector('[data-tab="profile"]')?->getAttribute('wire:key'))->toBe('tab-profile')
        ->and($billing?->hasAttribute('wire:key'))->toBeFalse()
        ->and($billing?->parentElement?->getAttribute('wire:key'))->toBe('tab-billing');
});

test('other attributes reach the Flux root of a tab bar', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-tabs :tabs="$tabs" :current-tab="$tabs[0]" variant="segmented" class="mt-6" />', ['tabs' => $this->tabs]), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-tabs]')?->classList->contains('mt-6'))->toBeTrue()
        ->and($document->querySelector('[data-flux-tabs]')?->classList->contains('inline-flex'))->toBeTrue();
});

test('no tab in a tab bar uses wire:navigate', function (): void {
    expect((string) $this->blade('<x-tabs :tabs="$tabs" :current-tab="$tabs[0]" />', ['tabs' => $this->tabs]))->not->toContain('wire:navigate');
});

test('a tab bar without tabs renders nothing', function (): void {
    expect(trim((string) $this->blade('<x-tabs :tabs="[]" :current-tab="null" />')))->toBe('');
});
