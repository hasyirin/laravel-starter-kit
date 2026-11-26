<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use Illuminate\Auth\Events\Failed;

dataset('two-factor feature', [
    'two-factor on' => [true],
    'two-factor off' => [false],
]);

function toggleTwoFactorForFailedSignIn(bool $on): void
{
    if ($on) {
        test()->skipUnlessFeature(Feature::TwoFactorAuthentication);

        return;
    }

    test()->disableFeature(Feature::TwoFactorAuthentication);
}

test('a wrong password records one failed sign-in entry with the user', function (bool $twoFactor): void {
    toggleTwoFactorForFailedSignIn($twoFactor);
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(SignInEntry::query()->sole()->only('event', 'result', 'method', 'two_factor', 'user_id'))->toBe([
        'event' => SignInEvent::FailedSignIn,
        'result' => SignInResult::WrongPassword,
        'method' => SignInMethod::Password,
        'two_factor' => false,
        'user_id' => $user->id,
    ]);
})->with('two-factor feature');

test('an email address with no user records one failed sign-in entry without a user or the typed email address', function (bool $twoFactor): void {
    toggleTwoFactorForFailedSignIn($twoFactor);

    $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'typed-secret'])
        ->assertSessionHasErrors('email');

    $entry = SignInEntry::query()->sole();
    expect($entry->only('event', 'result', 'method', 'user_id'))->toBe([
        'event' => SignInEvent::FailedSignIn,
        'result' => SignInResult::NoMatchingUser,
        'method' => SignInMethod::Password,
        'user_id' => null,
    ])
        ->and(json_encode($entry->getAttributes()))->not->toContain('nobody@example.com')->not->toContain('typed-secret');
})->with('two-factor feature');

test('a failed sign-in on another guard than the Fortify guard records nothing', function (): void {
    event(new Failed('other', User::factory()->create(), []));

    expect(SignInEntry::query()->count())->toBe(0);
});
