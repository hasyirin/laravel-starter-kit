<?php

declare(strict_types=1);

use App\Domain\User\Models\User;

test('the first page of a user without preferences loads again once with the browser preferences', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard', absolute: false));

    expect($page->script(browserScript(<<<'SCRIPT'
        return await until(() => window.Livewire?.all().length > 0)
            && document.querySelector('meta[name="browser-preferences"]') === null
            && sessionStorage.getItem('browser-preferences-reloaded') !== null;
        SCRIPT)))->toBeTrue()
        ->and($user->refresh()->timezone)->not->toBeNull()
        ->and($user->locale)->not->toBeNull();
});

test('the page of a user with preferences does not load again', function (): void {
    $this->actingAs(User::factory()->create(['timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'en-MY', 'language' => 'en']));

    $page = visit(route('dashboard', absolute: false));

    expect($page->script(browserScript(<<<'SCRIPT'
        return await until(() => window.Livewire?.all().length > 0)
            && sessionStorage.getItem('browser-preferences-reloaded') === null;
        SCRIPT)))->toBeTrue();
});
