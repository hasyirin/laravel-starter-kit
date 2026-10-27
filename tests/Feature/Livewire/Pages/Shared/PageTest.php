<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Tests\Fixtures\Livewire\StubPage;

test('a page uses the layout of the area that its route is in', function (string $route, string $layout): void {
    request()->setRouteResolver(fn () => resolve(Router::class)->getRoutes()->getByName($route));

    $view = new StubPage()->rendering(view('welcome'));

    expect($view->layoutConfig->view)->toBe($layout);
})->with([
    'a guest page' => ['home', 'layouts.guest'],
    'an app page' => ['dashboard', 'layouts.app'],
    'an internal page' => ['internal.dashboard', 'layouts.internal'],
    'an admin page' => ['admin.dashboard', 'layouts.admin'],
]);
