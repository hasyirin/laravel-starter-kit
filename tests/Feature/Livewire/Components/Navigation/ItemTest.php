<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Components\Navigation\Item;
use Dom\HTMLDocument;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

test('an item with a permission is accessible to a user who has it', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');
    $this->actingAs($user);

    $item = Item::make(label: 'Admin', accessible: 'admin.access');

    expect($item->accessible)->toBeTrue();
});

test('an item with a permission is hidden from a user without it', function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actingAs(User::factory()->create());

    $item = Item::make(label: 'Admin', accessible: 'admin.access');

    expect($item->accessible)->toBeFalse();
});

test('an item passes the arguments of an array ability to the gate', function (string $argument, bool $accessible): void {
    Gate::define('open-report', fn (User $user, string $report): bool => $report === 'sales');
    $this->actingAs(User::factory()->create());

    $item = Item::make(label: 'Report', accessible: ['open-report', $argument]);

    expect($item->accessible)->toBe($accessible);
})->with([
    'allowed argument' => ['sales', true],
    'refused argument' => ['payroll', false],
]);

test('an item is accessible as its flag says', function (bool $flag): void {
    $item = Item::make(label: 'Report', accessible: $flag);

    expect($item->accessible)->toBe($flag);
})->with(['shown' => true, 'hidden' => false]);

test('an item is accessible as its closure returns', function (bool $result): void {
    $item = Item::make(label: 'Report', accessible: fn (): bool => $result);

    expect($item->accessible)->toBe($result);
})->with(['shown' => true, 'hidden' => false]);

test('an item with children is accessible when any child is', function (): void {
    $item = Item::make(label: 'Reports', children: [
        Item::make(label: 'Payroll', accessible: false),
        Item::make(label: 'Sales'),
    ]);

    expect($item->accessible)->toBeTrue();
});

test('an item with children is hidden when no child is accessible', function (): void {
    $item = Item::make(label: 'Reports', children: [
        Item::make(label: 'Payroll', accessible: false),
    ]);

    expect($item->accessible)->toBeFalse();
});

test('an item with children is hidden when the item itself is not accessible', function (): void {
    $item = Item::make(label: 'Reports', accessible: false, children: [
        Item::make(label: 'Sales'),
    ]);

    expect($item->accessible)->toBeFalse();
});

test('an item keeps only the children the user may see', function (): void {
    $item = Item::make(label: 'Reports', children: [
        Item::make(label: 'Payroll', accessible: false),
        Item::make(label: 'Sales'),
    ]);

    expect($item->children->pluck('label')->all())->toBe(['Sales']);
});

test('an item rejects children that are not items', function (): void {
    expect(fn (): Item => Item::make(label: 'Reports', children: ['Sales']))->toThrow(UnexpectedValueException::class);
});

test('an item keeps a badge only when it is not empty', function (string|int $badge, string|int|null $kept): void {
    $item = Item::make(label: 'Inbox', badge: $badge);

    expect($item->badge)->toBe($kept);
})->with([
    'zero' => [0, null],
    'empty text' => ['', null],
    'count' => [3, 3],
    'text' => ['New', 'New'],
]);

test('an item is active only on its own route', function (string $current, bool $active): void {
    request()->setRouteResolver(fn () => resolve(Router::class)->getRoutes()->getByName($current));

    $item = Item::make(label: 'Dashboard', route: 'dashboard');

    expect($item->active())->toBe($active);
})->with([
    'on its route' => ['dashboard', true],
    'on another route' => ['home', false],
]);

test('an item is active on every route that its active pattern matches', function (): void {
    request()->setRouteResolver(fn () => resolve(Router::class)->getRoutes()->getByName('appearance.edit'));

    $item = Item::make(label: 'Settings', route: 'profile.edit', active: '*.edit');

    expect($item->active())->toBeTrue();
});

test('an item with children is active only when one of its children is', function (string $current, bool $active): void {
    request()->setRouteResolver(fn () => resolve(Router::class)->getRoutes()->getByName($current));

    $item = Item::make(label: 'Settings', children: [
        Item::make(label: 'Profile', route: 'profile.edit'),
        Item::make(label: 'Appearance', route: 'appearance.edit'),
    ]);

    expect($item->active())->toBe($active);
})->with([
    'on a child route' => ['appearance.edit', true],
    'on another route' => ['dashboard', false],
]);

test('an item links to its route', function (): void {
    $item = Item::make(label: 'Dashboard', route: 'dashboard');

    expect($item->href())->toBe(url('dashboard'));
});

test('an item without a route links to its href', function (): void {
    $item = Item::make(label: 'Docs', href: 'https://laravel.com/docs');

    expect($item->href())->toBe('https://laravel.com/docs');
});

test('an item with an unknown route fails instead of linking nowhere', function (): void {
    $item = Item::make(label: 'Reports', route: 'reports.index');

    expect(fn (): ?string => $item->href())->toThrow(RouteNotFoundException::class);
});

test('an item uses Livewire navigation only for a route', function (Item $item, bool $navigates): void {
    $view = $this->blade('{{ $item }}', ['item' => $item]);

    expect(str_contains((string) $view, 'wire:navigate'))->toBe($navigates);
})->with([
    'route' => [fn (): Item => Item::make(label: 'Dashboard', route: 'dashboard'), true],
    'href' => [fn (): Item => Item::make(label: 'Docs', href: 'https://laravel.com/docs'), false],
]);

test('an item with children renders them inside its group', function (): void {
    $item = Item::make(label: 'Reports', children: [
        Item::make(label: 'Sales', href: 'https://example.com/sales'),
    ]);

    $view = $this->blade('{{ $item }}', ['item' => $item]);

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    expect($document->querySelector('[data-flux-sidebar-group] [data-flux-sidebar-item][href="https://example.com/sales"]'))->not->toBeNull();
});
