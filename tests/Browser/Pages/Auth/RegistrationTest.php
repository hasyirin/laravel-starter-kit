<?php

declare(strict_types=1);

use App\Domain\User\Models\User;

test('the registration form posts the phone number in international format', function (): void {
    $page = visit(route('register', absolute: false));

    expect($page->script(browserScript("return await document.querySelector('ui-phone').iti.promise.then(() => true);")))->toBeTrue();

    $page->type('[name="name"]', 'John Doe')
        ->type('[name="email"]', 'john@example.com')
        ->type('ui-phone input[type="tel"]', '0123456789')
        ->type('[name="password"]', 'password')
        ->type('[name="password_confirmation"]', 'password')
        ->click('[data-test="register-user-button"]');

    expect($page->script(browserScript("return await until(() => location.pathname !== '/register');")))->toBeTrue();

    $page->assertNoJavaScriptErrors();

    expect(User::query()->where('email', 'john@example.com')->value('phone'))->toBe('+60123456789');
});
