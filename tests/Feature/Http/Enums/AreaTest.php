<?php

declare(strict_types=1);

use App\Http\Enums\Area;
use Illuminate\Routing\Router;

dataset('pages', [
    'a guest page' => ['home', Area::Guest],
    'an app page' => ['dashboard', Area::App],
    'an internal page' => ['internal.dashboard', Area::Internal],
    'a system page' => ['system.dashboard', Area::System],
]);

test('a request is in the area of its route', function (string $route, Area $area): void {
    request()->setRouteResolver(fn () => resolve(Router::class)->getRoutes()->getByName($route));

    expect(Area::current())->toBe($area);
})->with('pages');

test('a Livewire update is in the area of the page that sent it', function (string $route, Area $area): void {
    $path = resolve(Router::class)->getRoutes()->getByName($route)?->uri();
    request()->headers->set('X-Livewire', '1');
    request()->merge(['components' => [['snapshot' => json_encode(['memo' => ['path' => $path, 'method' => 'GET']])]]]);

    expect(Area::current())->toBe($area);
})->with('pages');

test('a Livewire update from a page without a route is in the guest area', function (): void {
    request()->headers->set('X-Livewire', '1');
    request()->merge(['components' => [['snapshot' => json_encode(['memo' => ['path' => 'no/such/page', 'method' => 'GET']])]]]);

    expect(Area::current())->toBe(Area::Guest);
});

test('an area gives the name that a route has in that area', function (Area $area, string $name): void {
    expect($area->route('security.edit'))->toBe($name);
})->with([
    'the guest area' => [Area::Guest, 'security.edit'],
    'the app area' => [Area::App, 'security.edit'],
    'the internal area' => [Area::Internal, 'internal.security.edit'],
    'the system area' => [Area::System, 'system.security.edit'],
]);
