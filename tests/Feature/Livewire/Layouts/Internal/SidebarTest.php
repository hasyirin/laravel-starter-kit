<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Layouts\Internal\Sidebar;
use Livewire\Livewire;

test('the internal sidebar links back to the app dashboard below a spacer', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('internal.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([route('internal.dashboard'), 'data-flux-sidebar-spacer', route('dashboard')], false)
        ->assertDontSee(route('admin.dashboard'));
});

test('the internal sidebar shows the admin link to users with admin access', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('internal.access', 'admin.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([route('dashboard'), route('admin.dashboard')], false);
});
