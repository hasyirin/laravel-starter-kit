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
        'admin.controls.users.update-roles',
        'admin.controls.users.update-permissions',
    );
    $this->actingAs($admin);

    Role::query()->create(['name' => 'editor', 'guard_name' => 'web'])->givePermissionTo('admin.access');
    Role::query()->create(['name' => 'viewer', 'guard_name' => 'web'])->givePermissionTo('internal.access');
    $this->subject = User::factory()->create();
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('admin.controls.users.view')->create();
});

function permissionTreeScript(string $body): string
{
    $helpers = <<<'SCRIPT'
        const tree = () => document.querySelector('[data-permission-tree]');
        const chip = (name) => tree().querySelector(`[data-permission="${name}"]`);
        const chips = (key) => [...tree().querySelectorAll(`[data-node="${key}"] [data-permission]`)];
        const ticked = (element) => element.getAttribute('aria-checked') === 'true';
        const locked = (element) => element.getAttribute('aria-disabled') === 'true';
        const shownChips = () => [...tree().querySelectorAll('[data-permission]')].filter((element) => element.checkVisibility());
        const role = (name) => document.querySelector(`ui-checkbox[value="${name}"]`);
        const openCard = async (heading) => {
            const toggle = [...document.querySelectorAll('[data-card-toggle]')].find((button) => button.textContent.trim() === heading);

            if (toggle.getAttribute('aria-expanded') === 'false') {
                toggle.click();
            }

            await until(() => toggle.getAttribute('aria-expanded') === 'true');
        };
        await until(() => tree() !== null);
        await openCard('Permissions');
        SCRIPT;

    return browserScript("{$helpers}\n{$body}");
}

test('ticking a resource box ticks all of its actions', function (): void {
    $page = visit(route('admin.controls.users.show', $this->subject, false));

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        tree().querySelector('[data-node="admin.controls.roles"] [data-resource-box]').click();

        return await until(() => chips('admin.controls.roles').length === 6 && chips('admin.controls.roles').every(ticked));
        SCRIPT)))->toBeTrue();

    $page->assertNoJavaScriptErrors();
});

test('search shows only matching actions', function (): void {
    $page = visit(route('admin.controls.users.show', $this->subject, false));

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        const search = tree().querySelector('input');
        search.value = 'upd';
        search.dispatchEvent(new Event('input', { bubbles: true }));

        await until(() => shownChips().length === 5);

        return shownChips().map((element) => [element.dataset.permission, element.querySelector('mark')?.textContent]);
        SCRIPT)))->toBe([
        ['admin.controls.roles.update', 'Upd'],
        ['admin.controls.roles.update-permissions', 'Upd'],
        ['admin.controls.users.update', 'Upd'],
        ['admin.controls.users.update-permissions', 'Upd'],
        ['admin.controls.users.update-roles', 'Upd'],
    ]);

    $page->assertNoJavaScriptErrors();
});

test('ticking a role marks its permissions as given by that role', function (): void {
    $page = visit(route('admin.controls.users.show', $this->subject, false));

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        await openCard('Roles');
        const fromEditor = ticked(chip('admin.access')) && locked(chip('admin.access')) && chip('admin.access').textContent.includes('from editor');

        role('viewer').click();
        role('editor').click();
        await until(() => ticked(chip('internal.access')) && ! ticked(chip('admin.access')));

        return fromEditor
            && locked(chip('internal.access'))
            && chip('internal.access').textContent.includes('from viewer')
            && ! locked(chip('admin.access'))
            && ! chip('admin.access').textContent.includes('from editor');
        SCRIPT)))->toBeTrue();

    $page->assertNoJavaScriptErrors();
});

test('the save button of the form saves the ticked permissions and keeps the search', function (): void {
    $page = visit(route('admin.controls.users.show', $this->subject, false));

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        const search = tree().querySelector('input');
        search.value = 'create';
        search.dispatchEvent(new Event('input', { bubbles: true }));
        await until(() => shownChips().length === 2);

        chip('admin.controls.users.create').click();
        await until(() => ticked(chip('admin.controls.users.create')));

        document.querySelector('form[data-card-group] button[type="submit"]').click();

        return await until(() => document.body.textContent.includes('User updated.'))
            && shownChips().length === 2
            && ticked(chip('admin.controls.users.create'));
        SCRIPT)))->toBeTrue();

    expect($this->subject->fresh()->profile->getPermissionNames()->sort()->values()->all())
        ->toBe(['admin.controls.users.create', 'admin.controls.users.view']);

    $page->assertNoJavaScriptErrors();
});

test('enter in the search does not send the form', function (): void {
    $page = visit(route('admin.controls.users.show', $this->subject, false));

    $page->script(permissionTreeScript(<<<'SCRIPT'
        tree().closest('form').addEventListener('submit', () => (window.formSent = true), { capture: true });
        SCRIPT));

    $page->keys('[data-permission-tree] input', 'Enter');

    expect($page->script('() => window.formSent === true'))->toBeFalse();

    $page->assertNoJavaScriptErrors();
});
