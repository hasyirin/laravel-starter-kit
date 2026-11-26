<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;

test('a wrong two-factor code records one failed sign-in entry with the user, the password method and two-factor', function (): void {
    $this->skipUnlessFeature(Feature::TwoFactorAuthentication);
    $user = User::factory()->withTwoFactor()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['recovery_code' => 'wrong-recovery-code'])
        ->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
    expect(SignInEntry::query()->sole()->only('event', 'result', 'method', 'two_factor', 'user_id'))->toBe([
        'event' => SignInEvent::FailedSignIn,
        'result' => SignInResult::WrongTwoFactorCode,
        'method' => SignInMethod::Password,
        'two_factor' => true,
        'user_id' => $user->id,
    ]);
});
