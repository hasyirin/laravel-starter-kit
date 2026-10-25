<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Illuminate\Routing\Router;

test('the user menu links to the settings of the current area', function (string $current, string $settings): void {
    $this->actingAs(User::factory()->create());
    request()->setRouteResolver(fn () => resolve(Router::class)->getRoutes()->getByName($current));

    $view = $this->blade('<x-user-menu />');

    $view->assertSee('href="'.route($settings).'"', false);
})->with([
    'on an app page' => ['dashboard', 'settings'],
    'on an internal page' => ['internal.dashboard', 'internal.settings'],
    'on an admin page' => ['admin.dashboard', 'admin.settings'],
]);
