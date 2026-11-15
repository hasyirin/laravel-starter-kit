<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

test('a language change in the system area loads the preferences page of the system area again', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create(['language' => 'en']);
    grantPermissions($user, 'system.access');
    $this->actingAs($user);

    $page = visit(route('system.preferences.edit', absolute: false));

    $page->script(browserScript(<<<'SCRIPT'
        const form = () => document.querySelector('form[wire\\:submit="save"]')?.closest('[wire\\:id]')?.__livewire;
        await until(() => form());
        await form().$wire.set('language', 'ms');
        form().$wire.save();
        return await until(() => document.documentElement.lang === 'ms');
        SCRIPT));

    expect($page->script('location.pathname'))->toBe('/system/settings/preferences')
        ->and($user->refresh()->language)->toBe('ms');

    $page->assertSee('Pilihan');
});
