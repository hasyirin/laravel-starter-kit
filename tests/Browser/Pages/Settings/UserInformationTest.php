<?php

declare(strict_types=1);

use App\Domain\User\Models\User;

test('saving without a change keeps the phone number verified', function (): void {
    $user = User::factory()->create(['phone' => '+60123456789']);
    $this->actingAs($user);

    $page = visit(route('settings', absolute: false));

    expect($page->script(browserScript("return await document.querySelector('ui-phone').iti.promise.then(() => true);")))->toBeTrue();

    $page->click('form[wire\:submit="save"] [type="submit"]');

    expect($page->script(browserScript("return await until(() => document.body.innerText.includes('User information updated.'));")))->toBeTrue();

    $page->assertNoJavaScriptErrors();

    expect($user->fresh())
        ->phone->toBe('+60123456789')
        ->phone_verified_at->not->toBeNull();
});
