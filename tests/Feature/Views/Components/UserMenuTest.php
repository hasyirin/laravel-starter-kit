<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use Illuminate\Routing\Router;

test('the user menu links to the settings of the current area', function (string $current, string $settings): void {
    $this->actingAs(User::factory()->create());
    request()->setRouteResolver(fn () => resolve(Router::class)->getRoutes()->getByName($current));

    $view = $this->blade('<x-user-menu />');

    $view->assertSee('href="'.route($settings).'"', false);
})->with([
    'on an app page' => ['dashboard', 'settings'],
    'on an internal page' => ['internal.dashboard', 'internal.settings'],
    'on a system page' => ['system.dashboard', 'system.settings'],
]);

test('the user menu shows the impersonator and a stop button while impersonating', function (): void {
    $impersonator = User::factory()->create(['name' => 'Ina Impersonator']);
    Impersonation::begin($impersonator, route('dashboard'));
    $this->actingAs(User::factory()->create());

    $view = $this->blade('<x-user-menu />');

    $view->assertSeeInOrder(['data-test="user-menu-button"', 'data-test="user-menu-impersonator"', 'Ina Impersonator'], false)
        ->assertDontSee(__('user/users.impersonation.by', ['name' => 'Ina Impersonator']))
        ->assertSee('action="'.route('impersonation.stop').'"', false);
});

test('the user menu shows no impersonator and no stop button otherwise', function (): void {
    $this->actingAs(User::factory()->create());

    $view = $this->blade('<x-user-menu />');

    $view->assertDontSee('data-test="user-menu-impersonator"', false)
        ->assertDontSee(route('impersonation.stop'), false);
});

test('the user menu shows the stop button when the impersonator was deleted', function (): void {
    $impersonator = User::factory()->create();
    Impersonation::begin($impersonator, route('dashboard'));
    $impersonator->delete();
    $this->actingAs(User::factory()->create());

    $view = $this->blade('<x-user-menu />');

    $view->assertSee('action="'.route('impersonation.stop').'"', false);
});
