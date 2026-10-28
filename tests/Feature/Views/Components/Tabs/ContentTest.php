<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\Tabs\DocumentsPanel;
use Tests\Fixtures\Livewire\Tabs\OwnerPanel;
use Tests\Fixtures\Livewire\Tabs\TabsHost;

function contentChildId(Testable $component, string $slug): ?string
{
    return $component->snapshot['memo']['children']['tab-content-'.$slug][1] ?? null;
}

function contentDocument(Testable $component): HTMLDocument
{
    return HTMLDocument::createFromString('<!doctype html>'.$component->html(), LIBXML_NOERROR);
}

test("only the current tab's content mounts", function (): void {
    Livewire::test(TabsHost::class)
        ->assertSeeLivewire(OwnerPanel::class)
        ->assertDontSeeLivewire(DocumentsPanel::class);
});

test('a load-fresh panel mounts a new instance after leaving and returning', function (): void {
    $component = Livewire::test(TabsHost::class);
    $firstId = contentChildId($component, 'owner');

    $component->call('switchTab', 'notes');

    expect(contentChildId($component, 'owner'))->toBeNull();

    $component->call('switchTab', 'owner');

    expect($firstId)->not->toBeNull()
        ->and(contentChildId($component, 'owner'))->not->toBeNull()->not->toBe($firstId);
});

test('a kept-alive panel keeps its instance', function (): void {
    $component = Livewire::test(TabsHost::class)->call('switchTab', 'documents');
    $firstId = contentChildId($component, 'documents');

    $component->call('switchTab', 'notes');

    $panel = contentDocument($component)->querySelector('[data-tab-panel="documents"]');

    expect(contentChildId($component, 'documents'))->toBe($firstId)
        ->and($panel?->getAttribute('style'))->toBe('display: none')
        ->and($panel?->getAttribute('x-show'))->toBe("\$wire.tab === 'documents'")
        ->and(contentDocument($component)->querySelector('[data-tab-panel="notes"]')?->hasAttribute('style'))->toBeFalse();

    $component->call('switchTab', 'documents');

    expect($firstId)->not->toBeNull()
        ->and(contentChildId($component, 'documents'))->toBe($firstId);
});

test('a kept-alive default panel also shows while the tab is empty', function (): void {
    $component = Livewire::test(TabsHost::class, ['hiddenTabs' => ['owner']]);

    expect(contentDocument($component)->querySelector('[data-tab-panel="documents"]')?->getAttribute('x-show'))
        ->toBe("\$wire.tab === 'documents' || \$wire.tab === ''");
});

test('a load-fresh panel hides at once when a kept-alive panel is opened', function (): void {
    $component = Livewire::test(TabsHost::class)
        ->call('switchTab', 'documents')
        ->call('switchTab', 'notes');

    expect(contentDocument($component)->querySelector('[data-tab-panel="notes"]')?->getAttribute('x-show'))
        ->toBe("! ['documents'].includes(\$wire.tab)");

    $component->call('switchTab', 'owner');

    expect(contentDocument($component)->querySelector('[data-tab-panel="owner"]')?->getAttribute('x-show'))
        ->toBe("! ['documents'].includes(\$wire.tab)")
        ->and(contentDocument(Livewire::test(TabsHost::class))->querySelector('[data-tab-panel="owner"]')?->hasAttribute('x-show'))->toBeFalse();
});

test('a kept-alive panel that becomes unavailable is removed', function (): void {
    $component = Livewire::test(TabsHost::class)
        ->call('switchTab', 'documents')
        ->call('switchTab', 'owner')
        ->set('disabledTabs', ['movements', 'documents']);

    expect(contentDocument($component)->querySelector('[data-tab-panel="documents"]'))->toBeNull()
        ->and(contentChildId($component, 'documents'))->toBeNull();
});

test('component params win over tab params', function (string $slug, string $text): void {
    Livewire::withQueryParams(['tab' => $slug])
        ->test(TabsHost::class, ['contentParams' => ['heading' => 'Page']])
        ->assertSeeText($text);
})->with([
    'livewire' => ['owner', 'Owner panel tab Page'],
    'view' => ['notes', 'Notes tab Page'],
]);

test('a lazy tab mounts its content lazily', function (): void {
    Livewire::test(TabsHost::class, ['lazyTabs' => ['owner']])
        ->assertSeeHtml('x-intersect="$wire.__lazyLoad(')
        ->assertDontSeeText('Owner panel');

    Livewire::withoutLazyLoading()
        ->test(TabsHost::class, ['lazyTabs' => ['owner']])
        ->assertDontSeeHtml('__lazyLoad')
        ->assertSeeText('Owner panel');
});

test('orientation picks the animation class', function (string $orientation, string $animation): void {
    $classes = contentDocument(Livewire::test(TabsHost::class, ['orientation' => $orientation]))
        ->querySelector('[data-tab-panel="owner"]')?->classList;

    expect($classes?->contains($animation))->toBeTrue()
        ->and($classes?->contains('motion-reduce:animate-none'))->toBeTrue();
})->with([
    'horizontal' => ['horizontal', 'animate-tab-enter-x'],
    'vertical' => ['vertical', 'animate-tab-enter-y'],
]);

test('the content dims while a tab switch runs', function (): void {
    $content = contentDocument(Livewire::test(TabsHost::class))->querySelector('[data-tab-content]');

    expect($content?->getAttribute('wire:loading.class'))->toBe('opacity-50')
        ->and($content?->getAttribute('wire:target'))->toBe('switchTab');
});
