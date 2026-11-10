<?php

declare(strict_types=1);

use App\Livewire\Components\Navigation\Group;
use App\Livewire\Components\Navigation\Item;
use App\Livewire\Components\Navigation\Style;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Routing\Router;

test('a group is accessible when any of its items is', function (): void {
    $group = Group::make('Reports', [
        Item::make(label: 'Payroll', accessible: false),
        Item::make(label: 'Sales'),
    ]);

    expect($group->accessible)->toBeTrue();
});

test('a group is hidden when none of its items is accessible', function (): void {
    $group = Group::make('Reports', [
        Item::make(label: 'Payroll', accessible: false),
    ]);

    expect($group->accessible)->toBeFalse();
});

test('a group keeps only the items the user may see', function (): void {
    $group = Group::make('Reports', [
        Item::make(label: 'Payroll', accessible: false),
        Item::make(label: 'Sales'),
    ]);

    expect($group->items->pluck('label')->all())->toBe(['Sales']);
});

test('a group renders its heading above its items', function (): void {
    $group = Group::make('Reports', [
        Item::make(label: 'Sales', href: 'https://example.com/sales'),
    ]);

    $view = $this->blade('{{ $group->render($style) }}', ['group' => $group, 'style' => Style::Sidebar]);

    $view->assertSeeInOrder(['Reports', 'https://example.com/sales']);
});

test('a labelled group opens its items in a dropdown in the top bar', function (): void {
    $group = Group::make('Reports', [
        Item::make(label: 'Sales', href: 'https://example.com/sales'),
    ]);

    $view = $this->blade('{{ $group->render($style) }}', ['group' => $group, 'style' => Style::Topbar]);

    $view->assertSeeInOrder(['data-flux-dropdown', 'Reports', 'data-flux-menu', 'https://example.com/sales'], false);
});

test('a labelled group in the top bar is marked current only when one of its items is', function (string $current, bool $marked): void {
    request()->setRouteResolver(fn () => resolve(Router::class)->getRoutes()->getByName($current));
    $group = Group::make('Settings', [
        Item::make(label: 'Preferences', route: 'preferences.edit'),
    ]);

    $view = $this->blade('{{ $group->render($style) }}', ['group' => $group, 'style' => Style::Topbar]);

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    expect($document->querySelector('[data-flux-dropdown] [data-flux-navbar-items][data-current]') instanceof Element)->toBe($marked);
})->with([
    'on the page of an item' => ['preferences.edit', true],
    'on another page' => ['dashboard', false],
]);

test('an unlabelled group shows a separator before its items in the top bar', function (): void {
    $group = Group::make(items: [
        Item::make(label: 'Sales', href: 'https://example.com/sales'),
    ]);

    $view = $this->blade('{{ $group->render($style) }}', ['group' => $group, 'style' => Style::Topbar]);

    $view->assertSeeInOrder(['data-orientation="vertical"', 'data-flux-separator', 'https://example.com/sales'], false)
        ->assertDontSee('data-flux-dropdown', false);
});

test('a group rejects entries that are not items', function (): void {
    expect(fn (): Group => Group::make('Reports', ['Sales']))->toThrow(UnexpectedValueException::class);
});
