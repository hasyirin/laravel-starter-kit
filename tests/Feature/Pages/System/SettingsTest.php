<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Layouts\System\Navigation;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'system.access');
    $this->actingAs($this->actor);
});

test('the settings page and each of its tabs open in the system layout', function (string $tab): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);

    $response = $this->get(route('system.settings', ['tab' => $tab]));

    $response->assertOk()->assertSeeLivewire(Navigation::class);
})->with(['user-information', 'preferences', 'security', 'account']);

test('each old settings url of the system area redirects to its tab in the system area', function (string $route, string $target): void {
    $response = $this->get(route($route));

    $response->assertRedirect($target);
})->with([
    'user information' => ['system.user-information.edit', '/system/settings'],
    'preferences' => ['system.preferences.edit', '/system/settings?tab=preferences'],
    'security' => ['system.security.edit', '/system/settings?tab=security'],
    'appearance' => ['system.appearance.edit', '/system/settings?tab=preferences'],
]);

test('the tabs of the system area link to the system settings page', function (): void {
    $response = $this->get(route('system.settings'));

    $response->assertSeeInOrder([
        'href="'.route('system.settings').'"',
        'href="'.route('system.settings', ['tab' => 'preferences']).'"',
        'href="'.route('system.settings', ['tab' => 'security']).'"',
        'href="'.route('system.settings', ['tab' => 'account']).'"',
    ], false);
});

test('the tabs still link to the system settings page after a tab switch', function (): void {
    $page = $this->get(route('system.settings'));

    $response = callLivewireAction($page, 'settings.tabs', 'switchTab', ['preferences']);

    expect($response->json('components.0.effects.html'))->toContain('href="'.route('system.settings', ['tab' => 'account']).'"');
});
