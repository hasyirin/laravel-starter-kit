<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Layouts\Internal\Navigation;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access');
    $this->actingAs($this->actor);
});

test('the settings page and each of its tabs open in the internal layout', function (string $tab): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);

    $response = $this->get(route('internal.settings', ['tab' => $tab]));

    $response->assertOk()->assertSeeLivewire(Navigation::class);
})->with(['user-information', 'preferences', 'security', 'account']);

test('each old settings url of the internal area redirects to its tab in the internal area', function (string $route, string $target): void {
    $response = $this->get(route($route));

    $response->assertRedirect($target);
})->with([
    'user information' => ['internal.user-information.edit', '/internal/settings'],
    'preferences' => ['internal.preferences.edit', '/internal/settings?tab=preferences'],
    'security' => ['internal.security.edit', '/internal/settings?tab=security'],
    'appearance' => ['internal.appearance.edit', '/internal/settings?tab=preferences'],
]);

test('the tabs of the internal area link to the internal settings page', function (): void {
    $response = $this->get(route('internal.settings'));

    $response->assertSeeInOrder([
        'href="'.route('internal.settings').'"',
        'href="'.route('internal.settings', ['tab' => 'preferences']).'"',
        'href="'.route('internal.settings', ['tab' => 'security']).'"',
        'href="'.route('internal.settings', ['tab' => 'account']).'"',
    ], false);
});

test('the tabs still link to the internal settings page after a tab switch', function (): void {
    $page = $this->get(route('internal.settings'));

    $response = callLivewireAction($page, 'settings.tabs', 'switchTab', ['preferences']);

    expect($response->json('components.0.effects.html'))->toContain('href="'.route('internal.settings', ['tab' => 'account']).'"');
});

test('an expired password confirmation on the internal security tab leads back to that tab', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $page = $this->get(route('internal.settings', ['tab' => 'security']));
    $this->withSession(['auth.password_confirmed_at' => time() - config()->integer('auth.password_timeout') - 1]);

    $response = callLivewireAction($page, 'settings.security', 'enable');

    $response->assertSessionHas('url.intended', route('internal.settings', ['tab' => 'security']));
});

test('a verified user who asks for a new verification email in the internal area goes to the internal dashboard', function (): void {
    $page = $this->get(route('internal.settings'));

    $response = callLivewireAction($page, 'settings.user-information', 'resendVerificationNotification');

    expect($response->json('components.0.effects.redirect'))->toBe(route('internal.dashboard', absolute: false));
});
