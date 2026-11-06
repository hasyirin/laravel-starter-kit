<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Layouts\Internal\Sidebar;
use Livewire\Livewire;

test('the internal sidebar links back to the app dashboard below a spacer', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'internal.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([route('internal.dashboard'), 'data-flux-sidebar-spacer', route('dashboard')], false)
        ->assertDontSee(route('system.dashboard'));
});

test('the internal sidebar shows the system link to users with system access', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'internal.access', 'system.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSee(route('system.dashboard'));
});

test('the users item sits under controls', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'internal.access', 'internal.controls.users.view-any');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([__('navigation.controls'), route('internal.controls.users.index')], false);
});

test('the sidebar links to the docs only in the local environment', function (string $environment, bool $shown): void {
    $this->seed(PermissionSeeder::class);
    app()->detectEnvironment(fn (): string => $environment);
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(Sidebar::class);

    expect(str_contains($component->html(), 'href="'.route('docs.components').'"'))->toBe($shown);
})->with([
    'local' => ['local', true],
    'production' => ['production', false],
]);
