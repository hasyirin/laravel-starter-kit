<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Layouts\Admin\Sidebar;
use Livewire\Livewire;

test('the admin sidebar links back to the app dashboard below a spacer', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([route('admin.dashboard'), 'data-flux-sidebar-spacer', route('dashboard')], false)
        ->assertDontSee(route('internal.dashboard'));
});

test('the admin sidebar shows the internal link to users with internal access', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('internal.access', 'admin.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([route('dashboard'), route('internal.dashboard')], false);
});
