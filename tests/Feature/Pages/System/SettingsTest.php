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

test('users with the system access permission are sent to the user information page of the system area', function (): void {
    $response = $this->get(route('system.settings'));

    $response->assertRedirect()->assertHeader('Location', route('system.user-information.edit', absolute: false));
});

test('each settings page of the system area opens in the system layout', function (string $route): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);

    $response = $this->get(route($route));

    $response->assertOk()->assertSeeLivewire(Navigation::class);
})->with([
    'user information' => 'system.user-information.edit',
    'preferences' => 'system.preferences.edit',
    'security' => 'system.security.edit',
    'appearance' => 'system.appearance.edit',
]);
