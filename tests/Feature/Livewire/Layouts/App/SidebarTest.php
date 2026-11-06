<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Layouts\App\Sidebar;
use Dom\HTMLDocument;
use Livewire\Livewire;

test('the sidebar shows the system link below a spacer to users with system access', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'system.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([route('dashboard'), 'data-flux-sidebar-spacer', route('system.dashboard')], false);
});

test('the sidebar shows the internal link below a spacer to users with internal access', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'internal.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([route('dashboard'), 'data-flux-sidebar-spacer', route('internal.dashboard')], false)
        ->assertDontSee(route('system.dashboard'));
});

test('the sidebar hides the internal and system links and their spacer from users without access', function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(Sidebar::class);

    $component->assertSee(route('dashboard'))
        ->assertDontSee(route('internal.dashboard'))
        ->assertDontSee(route('system.dashboard'))
        ->assertDontSeeHtml('data-flux-sidebar-spacer');
});

test('the sidebar marks the item of the current page', function (): void {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $response->assertOk();

    $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    expect($document->querySelector('a[data-flux-sidebar-item][data-current][href="'.route('dashboard').'"]'))->not->toBeNull();
});

test('the sidebar links to the docs only in the local environment', function (string $environment, bool $shown): void {
    app()->detectEnvironment(fn (): string => $environment);
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(Sidebar::class);

    expect(str_contains($component->html(), 'href="'.route('docs.components').'"'))->toBe($shown);
})->with([
    'local' => ['local', true],
    'production' => ['production', false],
]);
