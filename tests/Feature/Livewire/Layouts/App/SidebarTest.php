<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Layouts\App\Sidebar;
use Dom\HTMLDocument;
use Livewire\Livewire;

test('the sidebar shows the admin link below a spacer to users with admin access', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([route('dashboard'), 'data-flux-sidebar-spacer', route('admin.dashboard')], false);
});

test('the sidebar shows the internal link below a spacer to users with internal access', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('internal.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([route('dashboard'), 'data-flux-sidebar-spacer', route('internal.dashboard')], false)
        ->assertDontSee(route('admin.dashboard'));
});

test('the sidebar hides the internal and admin links and their spacer from users without access', function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(Sidebar::class);

    $component->assertSee(route('dashboard'))
        ->assertDontSee(route('internal.dashboard'))
        ->assertDontSee(route('admin.dashboard'))
        ->assertDontSeeHtml('data-flux-sidebar-spacer');
});

test('the sidebar marks the item of the current page', function (): void {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $response->assertOk();

    $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    expect($document->querySelector('a[data-flux-sidebar-item][data-current][href="'.route('dashboard').'"]'))->not->toBeNull();
});
