<?php

declare(strict_types=1);

use App\Livewire\Components\Navigation\Item;
use App\Livewire\Components\Navigation\Spacer;
use App\Livewire\Components\Navigation\Style;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\StubNavigation;

test('a navigation hides the entries the user may not see', function (): void {
    $component = Livewire::test(StubNavigation::class, ['entries' => [
        Item::make(label: 'Sales'),
        Item::make(label: 'Payroll', accessible: false),
    ]]);

    $component->assertSee('Sales')->assertDontSee('Payroll');
});

test('a navigation puts a spacer between two sections the user may see', function (): void {
    $component = Livewire::test(StubNavigation::class, ['entries' => [
        Item::make(label: 'Sales'),
        Spacer::make(),
        Item::make(label: 'System'),
    ]]);

    $component->assertSeeInOrder(['Sales', 'data-flux-sidebar-spacer', 'System'], false);
});

test('a navigation shows no spacer next to a section the user may not see', function (array $entries, int $spacers): void {
    $component = Livewire::test(StubNavigation::class, ['entries' => $entries]);

    expect(substr_count($component->html(), 'data-flux-sidebar-spacer'))->toBe($spacers);
})->with([
    'hidden section before it' => [fn (): array => [Item::make(label: 'Sales', accessible: false), Spacer::make(), Item::make(label: 'System')], 0],
    'hidden section after it' => [fn (): array => [Item::make(label: 'Sales'), Spacer::make(), Item::make(label: 'System', accessible: false)], 0],
    'hidden section between two spacers' => [fn (): array => [Item::make(label: 'Sales'), Spacer::make(), Item::make(label: 'Payroll', accessible: false), Spacer::make(), Item::make(label: 'System')], 1],
]);

test('a navigation in the top bar style shows its sections in a navbar with a spacer between them', function (): void {
    $component = Livewire::test(StubNavigation::class, ['style' => Style::Topbar, 'entries' => [
        Item::make(label: 'Sales'),
        Spacer::make(),
        Item::make(label: 'System'),
    ]]);

    $component->assertSeeInOrder(['data-flux-navbar', 'Sales', 'data-flux-spacer', 'System'], false)
        ->assertDontSeeHtml('data-flux-sidebar');
});
