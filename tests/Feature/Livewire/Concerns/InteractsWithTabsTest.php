<?php

declare(strict_types=1);

use App\Livewire\Components\Tab;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\Tabs\CountingTabsHost;
use Tests\Fixtures\Livewire\Tabs\NestedHost;
use Tests\Fixtures\Livewire\Tabs\TabsHost;

/**
 * @return array<string, ?string>
 */
function tabHrefs(Testable $component): array
{
    /** @var list<Tab> $tabs */
    $tabs = $component->viewData('tabs');

    return collect($tabs)->mapWithKeys(fn (Tab $tab): array => [$tab->slug() => $tab->resolvedUrl()])->all();
}

/**
 * @return list<string>
 */
function defaultTabSlugs(Testable $component): array
{
    /** @var list<Tab> $tabs */
    $tabs = $component->viewData('tabs');

    return collect($tabs)->filter(fn (Tab $tab): bool => $tab->isDefault())->map(fn (Tab $tab): string => $tab->slug())->values()->all();
}

/**
 * @return list<string>
 */
function panelSlugs(Testable $component): array
{
    /** @var list<Tab> $panels */
    $panels = $component->viewData('tabPanels');

    return array_map(fn (Tab $tab): string => $tab->slug(), $panels);
}

function laterLivewireRequest(string $referer): void
{
    Livewire::withHeaders(['X-Livewire' => 'true', 'Referer' => url($referer)])
        ->withQueryParams(['components' => [['snapshot' => json_encode(['memo' => ['path' => 'clients/1']])]]]);
}

test('mount keeps a valid tab from the URL', function (): void {
    Livewire::withQueryParams(['tab' => 'documents'])
        ->test(TabsHost::class)
        ->assertSet('tab', 'documents');
});

test('mount falls back to the first available tab', function (string $slug, array $hiddenTabs): void {
    Livewire::withQueryParams(['tab' => $slug])
        ->test(TabsHost::class, ['hiddenTabs' => $hiddenTabs])
        ->assertSet('tab', '')
        ->assertViewHas('currentTab', fn (Tab $tab): bool => $tab->slug() === 'owner');
})->with([
    'unknown' => ['missing', []],
    'hidden' => ['notes', ['notes']],
    'disabled' => ['movements', []],
]);

test('mount aborts with 404 when no tab is available', function (): void {
    Livewire::test(TabsHost::class, ['hiddenTabs' => ['owner', 'documents', 'notes']])
        ->assertNotFound();
});

test('switchTab ignores unknown, hidden, and disabled slugs', function (string $slug): void {
    Livewire::test(TabsHost::class, ['hiddenTabs' => ['notes']])
        ->call('switchTab', 'documents')
        ->call('switchTab', $slug)
        ->assertSet('tab', 'documents');
})->with(['missing', 'notes', 'movements']);

test('setting tab directly to an unavailable slug resets it', function (): void {
    Livewire::test(TabsHost::class)
        ->call('switchTab', 'notes')
        ->set('tab', 'movements')
        ->assertSet('tab', 'notes');
});

test('setting tab and switching in one request switches the tab', function (): void {
    Livewire::test(TabsHost::class)
        ->update(calls: [['method' => 'switchTab', 'params' => ['documents'], 'metadata' => []]], updates: ['tab' => 'documents'])
        ->assertSet('tab', 'documents')
        ->assertSet('visitedTabs', ['documents']);
});

test('a kept-alive tab is remembered after the first visit', function (): void {
    $component = Livewire::test(TabsHost::class)
        ->call('switchTab', 'documents')
        ->call('switchTab', 'notes')
        ->call('switchTab', 'owner');

    $component->assertSet('visitedTabs', ['documents']);
    expect(panelSlugs($component))->toBe(['owner', 'documents']);
});

test('a kept-alive tab that becomes unavailable leaves the panels', function (): void {
    $component = Livewire::test(TabsHost::class)
        ->call('switchTab', 'documents')
        ->call('switchTab', 'owner')
        ->set('disabledTabs', ['movements', 'documents']);

    expect(panelSlugs($component))->toBe(['owner']);
});

test('a current tab that becomes hidden falls back to the first available tab', function (): void {
    $component = Livewire::test(TabsHost::class)
        ->call('switchTab', 'notes')
        ->set('hiddenTabs', ['notes']);

    $component->assertSet('tab', '')
        ->assertViewHas('currentTab', fn (Tab $tab): bool => $tab->slug() === 'owner');
    expect(panelSlugs($component))->toBe(['owner']);
});

test('hrefs keep every other query parameter', function (): void {
    $component = Livewire::withQueryParams(['search' => 'bob', 'filters' => ['status' => 'open'], 'tab' => 'documents'])
        ->test(TabsHost::class);

    expect(tabHrefs($component))
        ->owner->toMatch('#^'.preg_quote(url('livewire-unit-test-endpoint'), '#').'/\w+\?search=bob&filters%5Bstatus%5D=open$#')
        ->notes->toMatch('#^'.preg_quote(url('livewire-unit-test-endpoint'), '#').'/\w+\?search=bob&filters%5Bstatus%5D=open&tab=notes$#');
});

test('hrefs use the Referer query and the page path on a Livewire request', function (): void {
    laterLivewireRequest('/clients/1?search=bob');

    $component = Livewire::test(TabsHost::class);

    expect(tabHrefs($component))
        ->owner->toBe(url('clients/1?search=bob'))
        ->documents->toBe(url('clients/1?search=bob&tab=documents'));
});

test('a nested set reads its own key from the Referer when it mounts in a later request', function (): void {
    laterLivewireRequest('/clients/1?tab=owner&holding=documents');

    Livewire::test(NestedHost::class)
        ->assertSet('tab', 'documents');
});

test('the default tab keeps the key out of the URL', function (): void {
    $component = Livewire::withQueryParams(['tab' => 'owner'])->test(TabsHost::class);

    $component->assertSet('tab', '');
    expect(defaultTabSlugs($component))->toBe(['owner'])
        ->and($component->effects['url']['tab']['except'])->toBe('')
        ->and(tabHrefs($component)['owner'])->toMatch('#^'.preg_quote(url('livewire-unit-test-endpoint'), '#').'/\w+$#');

    $component->call('switchTab', 'notes')
        ->call('switchTab', 'owner')
        ->assertSet('tab', '');
});

test('setting tab to the default slug from the client stores an empty tab', function (): void {
    Livewire::test(TabsHost::class)
        ->call('switchTab', 'notes')
        ->set('tab', 'owner')
        ->assertSet('tab', '');
});

test('the default tab is the first available, not the first declared', function (): void {
    $component = Livewire::withQueryParams(['tab' => 'documents'])
        ->test(TabsHost::class, ['hiddenTabs' => ['owner']]);

    $component->assertSet('tab', '');
    expect(tabHrefs($component)['documents'])->toMatch('#^'.preg_quote(url('livewire-unit-test-endpoint'), '#').'/\w+$#')
        ->and(tabHrefs($component)['notes'])->toEndWith('?tab=notes')
        ->and(defaultTabSlugs($component))->toBe(['documents']);
});

test('when the first available tab changes, an empty tab follows the new default', function (): void {
    $component = Livewire::test(TabsHost::class)
        ->set('hiddenTabs', ['owner']);

    $component->assertSet('tab', '')
        ->assertViewHas('currentTab', fn (Tab $tab): bool => $tab->slug() === 'documents');
    expect(tabHrefs($component)['documents'])->toMatch('#^'.preg_quote(url('livewire-unit-test-endpoint'), '#').'/\w+$#')
        ->and(defaultTabSlugs($component))->toBe(['documents']);
});

test('queryTabAs changes the URL key', function (): void {
    $component = Livewire::test(NestedHost::class);

    expect($component->effects['url']['tab']['as'])->toBe('holding')
        ->and(tabHrefs($component)['documents'])->toEndWith('?holding=documents');
});

test('an action that reads the current tab and then switches renders the new tab', function (): void {
    $component = Livewire::test(TabsHost::class)
        ->call('continueToDocuments');

    $component->assertSet('tab', 'documents')
        ->assertViewHas('currentTab', fn (Tab $tab): bool => $tab->slug() === 'documents')
        ->assertSee('Documents panel');
    expect(panelSlugs($component))->toBe(['documents']);
});

test('the tab list is built once per phase', function (): void {
    CountingTabsHost::$tabsCalls = 0;

    $component = Livewire::test(CountingTabsHost::class);

    expect(CountingTabsHost::$tabsCalls)->toBe(2);

    CountingTabsHost::$tabsCalls = 0;

    $component->update(calls: [['method' => 'switchTab', 'params' => ['documents'], 'metadata' => []]], updates: ['tab' => 'documents']);

    expect(CountingTabsHost::$tabsCalls)->toBe(3);
});
