<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->givePermissionTo(
        'admin.access',
        'admin.controls.users.view',
        'admin.controls.users.update',
        'admin.controls.users.update-permissions',
    );
    $this->actingAs($admin);

    Role::query()->create(['name' => 'editor', 'guard_name' => 'web'])->givePermissionTo('admin.access');
    $this->subject = User::factory()->create();
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('admin.controls.users.view')->create();
});

function permissionTreeScript(string $body): string
{
    $helpers = <<<'SCRIPT'
        const tree = () => document.querySelector('[data-permission-tree]');
        const chips = (key) => [...tree().querySelectorAll(`[data-node="${key}"] [data-permission]`)];
        const ticked = (chip) => chip.getAttribute('aria-checked') === 'true';
        const shownChips = () => [...tree().querySelectorAll('[data-permission]')].filter((chip) => chip.checkVisibility());
        const unsaved = () => tree().querySelector('[data-unsaved]');
        await until(() => tree() !== null);
        SCRIPT;

    return browserScript("{$helpers}\n{$body}");
}

function visitPermissions(User $user): mixed
{
    return visit(route('admin.controls.users.show', ['user' => $user, 'tab' => 'permissions'], false));
}

test('ticking a resource box ticks all of its actions', function (): void {
    $page = visitPermissions($this->subject);

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        tree().querySelector('[data-node="admin.controls.roles"] [data-resource-box]').click();

        return await until(() => chips('admin.controls.roles').length === 6 && chips('admin.controls.roles').every(ticked))
            && unsaved().textContent.trim() === '6 unsaved changes';
        SCRIPT)))->toBeTrue();

    $page->assertNoJavaScriptErrors();
});

test('search shows only matching actions', function (): void {
    $page = visitPermissions($this->subject);

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        const search = tree().querySelector('input');
        search.value = 'upd';
        search.dispatchEvent(new Event('input', { bubbles: true }));

        await until(() => shownChips().length === 5);

        return shownChips().map((chip) => [chip.dataset.permission, chip.querySelector('mark')?.textContent]);
        SCRIPT)))->toBe([
        ['admin.controls.roles.update', 'Upd'],
        ['admin.controls.roles.update-permissions', 'Upd'],
        ['admin.controls.users.update', 'Upd'],
        ['admin.controls.users.update-permissions', 'Upd'],
        ['admin.controls.users.update-roles', 'Upd'],
    ]);

    $page->assertNoJavaScriptErrors();
});

test('saving shows the toast and clears the unsaved count', function (): void {
    $page = visitPermissions($this->subject);

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        const search = tree().querySelector('input');
        search.value = 'create';
        search.dispatchEvent(new Event('input', { bubbles: true }));
        await until(() => shownChips().length === 2);

        tree().querySelector('[data-permission="admin.controls.users.create"]').click();
        await until(() => unsaved().checkVisibility());

        tree().querySelector('[data-permission-save]').click();

        return await until(() => document.body.textContent.includes('The permissions are saved.') && ! unsaved().checkVisibility())
            && shownChips().length === 2;
        SCRIPT)))->toBeTrue();

    expect($this->subject->fresh()->profile->getPermissionNames()->sort()->values()->all())
        ->toBe(['admin.controls.users.create', 'admin.controls.users.view']);

    $page->assertNoJavaScriptErrors();
});
