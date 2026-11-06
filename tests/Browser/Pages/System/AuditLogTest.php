<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Pest\Browser\Api\PendingAwaitablePage;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->viewer = User::factory()->create(['name' => 'Vera Viewer']);
    grantPermissions($this->viewer, 'system.access', 'system.audit-log.view');
    $this->actingAs($this->viewer);
    $this->viewer->update(['name' => 'Vera Renamed']);
});

function openAuditLogFilters(): PendingAwaitablePage
{
    $page = visit(route('system.audit-log', absolute: false));

    $page->script(browserScript(<<<'SCRIPT'
        await until(() => document.querySelector('.fi-ta-row'));
        document.querySelector('.fi-ta-filters-dropdown .fi-dropdown-trigger').dispatchEvent(new MouseEvent('mousedown', { bubbles: true, button: 0 }));
        await until(() => document.querySelector('.fi-ta-filters').checkVisibility());
        SCRIPT));

    return $page;
}

function pickType(PendingAwaitablePage $page, string $filter, string $type): void
{
    $page->script(browserScript(<<<SCRIPT
        const select = document.querySelector('[id="tableFiltersForm.{$filter}.type"]');
        select.value = '{$type}';
        select.dispatchEvent(new Event('change', { bubbles: true }));
        await until(() => wire(select).\$get('tableDeferredFilters.{$filter}.type') === '{$type}');
        SCRIPT));
}

function recordSelectShown(PendingAwaitablePage $page, string $filter, bool $shown): bool
{
    $expected = $shown ? 'true' : 'false';

    return $page->script(browserScript(<<<SCRIPT
        return await until(() => {
            const wrapper = document.querySelector('[wire\\\\:partial="schema-component::tableFiltersForm.{$filter}.ids"]');

            return (wrapper.innerHTML.trim() !== '' && wrapper.checkVisibility()) === {$expected};
        });
        SCRIPT));
}

test('the record select shows only for a type whose records have names', function (): void {
    $page = openAuditLogFilters();

    pickType($page, 'causer', 'user');
    expect(recordSelectShown($page, 'causer', true))->toBeTrue();

    pickType($page, 'causer', 'none');
    expect(recordSelectShown($page, 'causer', false))->toBeTrue();

    pickType($page, 'subject', 'role');
    expect(recordSelectShown($page, 'subject', true))->toBeTrue();

    pickType($page, 'subject', 'profile');
    expect(recordSelectShown($page, 'subject', false))->toBeTrue();

    $page->assertNoJavaScriptErrors();
});

test('the record select finds users by name, and a new type clears the picked users', function (): void {
    $ada = User::factory()->create(['name' => 'Ada Lovelace']);
    User::factory()->create(['name' => 'Sam Subject']);
    $page = openAuditLogFilters();

    pickType($page, 'causer', 'user');
    expect(recordSelectShown($page, 'causer', true))->toBeTrue();

    $page->script(browserScript(<<<'SCRIPT'
        const wrapper = document.querySelector('[wire\\:partial="schema-component::tableFiltersForm.causer.ids"]');
        wrapper.querySelector('button').click();
        await until(() => wrapper.querySelector('input[role="combobox"]')?.checkVisibility());
        const input = wrapper.querySelector('input[role="combobox"]');
        input.value = 'ada';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        SCRIPT));

    $picked = $page->script(browserScript(<<<'SCRIPT'
        const wrapper = document.querySelector('[wire\\:partial="schema-component::tableFiltersForm.causer.ids"]');
        const input = wrapper.querySelector('input[role="combobox"]');
        const options = () => [...document.querySelectorAll(`#${input.getAttribute('aria-controls')} [role="option"]`)];
        await until(() => options().some((option) => option.textContent.includes('Ada Lovelace')));
        const names = options().map((option) => option.textContent.trim());
        options().find((option) => option.textContent.includes('Ada Lovelace')).click();

        const component = wire(wrapper);
        await until(() => component.$get('tableDeferredFilters.causer.ids').length === 1);

        return { names, ids: component.$get('tableDeferredFilters.causer.ids') };
        SCRIPT));

    expect($picked['names'])->toBe(['Ada Lovelace'])
        ->and($picked['ids'])->toBe([(string) $ada->id]);

    pickType($page, 'causer', 'none');

    expect($page->script(browserScript(<<<'SCRIPT'
        const component = wire(document.querySelector('[id="tableFiltersForm.causer.type"]'));

        return await until(() => component.$get('tableDeferredFilters.causer.ids').length === 0);
        SCRIPT)))->toBeTrue();

    $page->assertNoJavaScriptErrors();
});
