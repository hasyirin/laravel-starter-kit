<?php

declare(strict_types=1);

use App\Domain\User\Models\User;

test('the delete account dialog deletes the account and signs the user out', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('user-information.edit', absolute: false));

    $page->click('[data-flux-modal-trigger] button')
        ->type('[name="password"]', 'password')
        ->click('form[wire\:submit="delete"] [type="submit"]');

    expect($page->script(browserScript("return await until(() => location.pathname === '/');")))->toBeTrue();

    $page->assertSee('Log in')
        ->assertNoJavaScriptErrors();

    expect($user->fresh()?->trashed())->toBeTrue();
});

test('saving without a change keeps the phone number verified', function (): void {
    $user = User::factory()->create(['phone' => '+60123456789']);
    $this->actingAs($user);

    $page = visit(route('user-information.edit', absolute: false));

    expect($page->script(browserScript("return await document.querySelector('ui-phone').iti.promise.then(() => true);")))->toBeTrue();

    $page->click('form[wire\:submit="save"] [type="submit"]');

    expect($page->script(browserScript("return await until(() => document.body.innerText.includes('User information updated.'));")))->toBeTrue();

    $page->assertNoJavaScriptErrors();

    expect($user->fresh())
        ->phone->toBe('+60123456789')
        ->phone_verified_at->not->toBeNull();
});
