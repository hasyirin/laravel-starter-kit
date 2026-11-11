<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $actor = User::factory()->create();
    grantPermissions($actor,
        'internal.access',
        'internal.controls.users.view',
        'internal.controls.users.update',
        'internal.controls.users.update-roles',
        'internal.controls.users.update-permissions',
    );
    $this->actingAs($actor);

    Role::query()->create(['name' => 'editor', 'guard_name' => 'web'])->givePermissionTo('system.access');
    Role::query()->create(['name' => 'viewer', 'guard_name' => 'web'])->givePermissionTo('internal.access');
    $this->subject = User::factory()->create();
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('internal.controls.users.view')->create();
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
    $page = visit(route('internal.controls.users.show', $this->subject, false));

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        tree().querySelector('[data-node="internal.controls.roles"] [data-resource-box]').click();

        return await until(() => chips('internal.controls.roles').length === 7 && chips('internal.controls.roles').every(ticked));
        SCRIPT)))->toBeTrue();

    $page->assertNoJavaScriptErrors();
});

test('search shows only matching actions', function (): void {
    $page = visit(route('internal.controls.users.show', $this->subject, false));

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        const search = tree().querySelector('input');
        search.value = 'upd';
        search.dispatchEvent(new Event('input', { bubbles: true }));

        await until(() => shownChips().length === 7);

        return shownChips().map((element) => [element.dataset.permission, element.querySelector('mark')?.textContent]);
        SCRIPT)))->toBe([
        ['internal.controls.roles.update', 'Upd'],
        ['internal.controls.roles.update-permissions', 'Upd'],
        ['internal.controls.users.update', 'Upd'],
        ['internal.controls.users.update-permissions', 'Upd'],
        ['internal.controls.users.update-roles', 'Upd'],
        ['internal.controls.organizations.update', 'Upd'],
        ['internal.controls.letterheads.update', 'Upd'],
    ]);

    $page->assertNoJavaScriptErrors();
});

test('ticking a role marks its permissions as given by that role', function (): void {
    $page = visit(route('internal.controls.users.show', $this->subject, false));

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        await openCard('Roles');
        const fromEditor = ! ticked(chip('system.access')) && ! locked(chip('system.access')) && chip('system.access').textContent.includes('from editor');

        role('viewer').click();
        role('editor').click();
        await until(() => chip('internal.access').textContent.includes('from viewer') && ! chip('system.access').textContent.includes('from editor'));

        return fromEditor
            && ! ticked(chip('internal.access'))
            && ! locked(chip('internal.access'));
        SCRIPT)))->toBeTrue();

    $page->assertNoJavaScriptErrors();
});

test('a permission that a role gives can also be ticked as direct, and stays when the role is removed', function (): void {
    $page = visit(route('internal.controls.users.show', $this->subject, false));

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        await openCard('Roles');

        chip('system.access').click();
        await until(() => ticked(chip('system.access')));
        const directAndFromEditor = chip('system.access').textContent.includes('from editor');

        role('editor').click();
        await until(() => ! chip('system.access').textContent.includes('from editor'));

        return directAndFromEditor && ticked(chip('system.access'));
        SCRIPT)))->toBeTrue();

    $page->assertNoJavaScriptErrors();
});

test('the save button of the form saves the ticked permissions and keeps the search', function (): void {
    $page = visit(route('internal.controls.users.show', $this->subject, false));

    expect($page->script(permissionTreeScript(<<<'SCRIPT'
        const search = tree().querySelector('input');
        search.value = 'create';
        search.dispatchEvent(new Event('input', { bubbles: true }));
        await until(() => shownChips().length === 4);

        chip('internal.controls.users.create').click();
        await until(() => ticked(chip('internal.controls.users.create')));

        document.querySelector('form[data-card-group] button[type="submit"]').click();

        return await until(() => document.body.textContent.includes('User updated.'))
            && shownChips().length === 4
            && ticked(chip('internal.controls.users.create'));
        SCRIPT)))->toBeTrue();

    expect($this->subject->fresh()->profile->getPermissionNames()->sort()->values()->all())
        ->toBe(['internal.controls.users.create', 'internal.controls.users.view']);

    $page->assertNoJavaScriptErrors();
});

test('enter in the search does not send the form', function (): void {
    $page = visit(route('internal.controls.users.show', $this->subject, false));

    $page->script(permissionTreeScript(<<<'SCRIPT'
        tree().closest('form').addEventListener('submit', () => (window.formSent = true), { capture: true });
        SCRIPT));

    $page->keys('[data-permission-tree] input', 'Enter');

    expect($page->script('() => window.formSent === true'))->toBeFalse();

    $page->assertNoJavaScriptErrors();
});
