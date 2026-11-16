<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

test('a language change in the system area loads the preferences page of the system area again', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create(['language' => 'en']);
    grantPermissions($user, 'system.access');
    $this->actingAs($user);

    $page = visit(route('system.settings', ['tab' => 'preferences'], absolute: false));

    $page->script(browserScript(<<<'SCRIPT'
        const form = () => document.querySelector('form[wire\\:submit="save"]')?.closest('[wire\\:id]')?.__livewire;
        await until(() => form());
        await form().$wire.set('language', 'ms');
        form().$wire.save();
        return await until(() => document.documentElement.lang === 'ms');
        SCRIPT));

    expect($page->script('location.pathname'))->toBe('/system/settings')
        ->and($page->script('location.search'))->toBe('?tab=preferences')
        ->and($user->refresh()->language)->toBe('ms');

    $page->assertSee('Pilihan');
});

test('a theme choice applies at once and stays selected after the preferences render again', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit(route('settings', ['tab' => 'preferences'], absolute: false));

    $applied = $page->script(browserScript(<<<'SCRIPT'
        const option = (value) => document.querySelector(`ui-radio[value="${value}"]`);
        await until(() => option('light'));
        option('light').click();
        await until(() => ! document.documentElement.classList.contains('dark'));
        option('dark').click();
        return await until(() => document.documentElement.classList.contains('dark'));
        SCRIPT));

    expect($applied)->toBeTrue();

    $selected = $page->script(browserScript(<<<'SCRIPT'
        const form = document.querySelector('form[wire\\:submit="save"]');
        const example = () => form.querySelector('[wire\\:model\\.live="locale"]').closest('[data-flux-field]').querySelector('[data-flux-description]').textContent.trim();
        const before = example();
        await form.closest('[wire\\:id]').__livewire.$wire.set('timezone', 'Pacific/Auckland');
        return await until(() => example() !== before)
            && document.querySelector('ui-radio[value="dark"]').hasAttribute('data-checked')
            && document.documentElement.classList.contains('dark');
        SCRIPT));

    expect($selected)->toBeTrue();

    $page->assertNoJavaScriptErrors();
});
