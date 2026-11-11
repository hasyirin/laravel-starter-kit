<?php

declare(strict_types=1);

use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\User\Models\User;
use Tests\Fixtures\Gateways\FakeGateway;

test('the sms reset page sends the code in a real request, and the code opens the new password form', function (): void {
    app()->instance(SmsGateway::class, $gateway = new FakeGateway);
    $user = User::factory()->create(['phone' => '+60123456789']);

    $page = visit(route('password.sms', absolute: false));

    expect($page->script(browserScript("return await document.querySelector('ui-phone').iti.promise.then(() => true);")))->toBeTrue();

    $page->type('ui-phone input[type="tel"]', '0123456789')
        ->click('form[wire\:submit="send"] [type="submit"]');

    expect($page->script(browserScript("return await until(() => document.querySelector('ui-otp') !== null);")))->toBeTrue()
        ->and($gateway->sent)->toHaveCount(1)
        ->and($gateway->sent[0]['phone'])->toBe('+60123456789');

    $page->type('[aria-label="Character 1 of 6"]', str($gateway->sent[0]['message'])->match('/\d{6}/')->value())
        ->click('form[wire\:submit="confirm"] [type="submit"]');

    expect($page->script(browserScript("return await until(() => location.pathname.startsWith('/reset-password/'));")))->toBeTrue();

    $page->assertValue('[name="email"]', $user->email)
        ->assertNoJavaScriptErrors();
});
