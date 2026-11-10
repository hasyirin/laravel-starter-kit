<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Layouts\Local\Navigation;
use App\Livewire\Pages\Local\ComponentDocs;
use Livewire\Livewire;

test('the navigation links to the overview and every component page', function (): void {
    $component = Livewire::test(Navigation::class);

    $component->assertSeeInOrder([
        route('docs.components'),
        ...ComponentDocs::documents()->keys()->map(fn (string $page): string => route('docs.components', $page)),
    ], false);
});

test('the navigation hides the area links from guests', function (): void {
    $component = Livewire::test(Navigation::class);

    $component->assertDontSee(route('dashboard'))
        ->assertDontSeeHtml('data-flux-sidebar-spacer');
});

test('the navigation shows the area links below a spacer to users with access', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'internal.access', 'system.access');
    $this->actingAs($user);

    $component = Livewire::test(Navigation::class);

    $component->assertSeeInOrder(['data-flux-sidebar-spacer', route('dashboard'), route('internal.dashboard'), route('system.dashboard')], false);
});
