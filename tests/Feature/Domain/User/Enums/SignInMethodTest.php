<?php

declare(strict_types=1);

use App\Domain\User\Enums\SignInMethod;

test('the route of a sign-in gives its method', function (?string $routeName, ?SignInMethod $method): void {
    expect(SignInMethod::fromRoute($routeName))->toBe($method);
})->with([
    'the password form' => ['login.store', SignInMethod::Password],
    'the two-factor challenge' => ['two-factor.login.store', SignInMethod::Password],
    'the registration form' => ['register.store', SignInMethod::Password],
    'a passkey' => ['passkey.login', SignInMethod::Passkey],
    'another route' => ['dashboard', null],
    'no route' => [null, null],
]);

test('every method has a label', function (SignInMethod $method): void {
    expect($method->label())->not->toStartWith('user/');
})->with(SignInMethod::cases());
