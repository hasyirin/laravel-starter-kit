<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Layouts\Internal\Sidebar;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access');
    $this->actingAs($this->actor);
});

test('users with the internal access permission are sent to the user information page of the internal area', function (): void {
    $response = $this->get(route('internal.settings'));

    $response->assertRedirect()->assertHeader('Location', route('internal.user-information.edit', absolute: false));
});

test('each settings page of the internal area opens in the internal layout', function (string $route): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);

    $response = $this->get(route($route));

    $response->assertOk()->assertSeeLivewire(Sidebar::class);
})->with([
    'user information' => 'internal.user-information.edit',
    'preferences' => 'internal.preferences.edit',
    'security' => 'internal.security.edit',
    'appearance' => 'internal.appearance.edit',
]);

test('the settings menu of the internal area links to the internal settings pages', function (): void {
    $response = $this->get(route('internal.user-information.edit'));

    $response->assertSeeInOrder([
        'href="'.route('internal.user-information.edit').'"',
        'href="'.route('internal.preferences.edit').'"',
        'href="'.route('internal.security.edit').'"',
        'href="'.route('internal.appearance.edit').'"',
    ], false);
});

test('the settings menu still links to the internal settings pages after a save', function (): void {
    $page = $this->get(route('internal.user-information.edit'));

    $response = callLivewireAction($page, 'settings.user-information', 'save');

    expect($response->json('components.0.effects.html'))->toContain('href="'.route('internal.preferences.edit').'"');
});

test('an expired password confirmation on the internal security page leads back to that page', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $page = $this->get(route('internal.security.edit'));
    $this->withSession(['auth.password_confirmed_at' => time() - config()->integer('auth.password_timeout') - 1]);

    $response = callLivewireAction($page, 'settings.security', 'enable');

    $response->assertSessionHas('url.intended', route('internal.security.edit'));
});

test('a verified user who asks for a new verification email in the internal area goes to the internal dashboard', function (): void {
    $page = $this->get(route('internal.user-information.edit'));

    $response = callLivewireAction($page, 'settings.user-information', 'resendVerificationNotification');

    expect($response->json('components.0.effects.redirect'))->toBe(route('internal.dashboard', absolute: false));
});
