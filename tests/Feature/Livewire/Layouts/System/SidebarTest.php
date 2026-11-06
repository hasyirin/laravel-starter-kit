<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Layouts\System\Sidebar;
use Dom\HTMLDocument;
use Livewire\Livewire;

test('the system sidebar links back to the app dashboard below a spacer', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'system.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSeeInOrder([route('system.dashboard'), 'data-flux-sidebar-spacer', route('dashboard')], false)
        ->assertDontSee(route('internal.dashboard'));
});

test('the system sidebar shows the internal link to users with internal access', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'internal.access', 'system.access');
    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $component->assertSee(route('internal.dashboard'));
});

test('the system sidebar shows the horizon link only to users who can view horizon', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'system.access');
    $this->actingAs($user);

    Livewire::test(Sidebar::class)->assertDontSee(route('horizon.index'));

    grantPermissions($user, 'system.horizon.view');

    Livewire::test(Sidebar::class)->assertSeeInOrder([route('system.dashboard'), route('horizon.index'), 'data-flux-sidebar-spacer'], false);
});

test('the horizon link loads the whole page, without wire:navigate', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'system.access', 'system.horizon.view');
    $this->actingAs($user);

    $document = HTMLDocument::createFromString(Livewire::test(Sidebar::class)->html(), LIBXML_NOERROR);
    $link = $document->querySelector('a[href="'.route('horizon.index').'"]');

    expect($link)->not->toBeNull()
        ->and($link?->hasAttribute('wire:navigate'))->toBeFalse();
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

test('the system sidebar shows the audit log link only to users who can view the audit log', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'system.access');
    $this->actingAs($user);

    Livewire::test(Sidebar::class)->assertDontSee(route('system.audit-log'));

    grantPermissions($user, 'system.audit-log.view');

    Livewire::test(Sidebar::class)->assertSeeInOrder([route('system.dashboard'), route('system.audit-log'), 'data-flux-sidebar-spacer'], false);
});
