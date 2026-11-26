<?php

declare(strict_types=1);

use App\Domain\User\Enums\SignInResult;

test('a successful sign-in is green and every failed one is red', function (SignInResult $result, string $color): void {
    expect($result->color())->toBe($color);
})->with([
    'signed in' => [SignInResult::SignedIn, 'green'],
    'wrong password' => [SignInResult::WrongPassword, 'red'],
    'no matching user' => [SignInResult::NoMatchingUser, 'red'],
    'wrong two-factor code' => [SignInResult::WrongTwoFactorCode, 'red'],
    'passkey failed' => [SignInResult::PasskeyFailed, 'red'],
    'locked out' => [SignInResult::LockedOut, 'red'],
]);

test('every result has a label', function (SignInResult $result): void {
    expect($result->label())->not->toStartWith('user/');
})->with(SignInResult::cases());
