<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PasswordResetCode;
use App\Domain\User\Notifications\PasswordWasReset;
use App\Domain\User\Notifications\SmsPasswordResetStarted;
use App\Domain\User\Rules\PhoneNumber;
use App\Livewire\Auth\SmsPasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('the forgot password page links to the sms reset page', function (): void {
    $this->get(route('password.request'))->assertOk()->assertSee(route('password.sms'));
});

test('the sms reset page opens', function (): void {
    $this->get(route('password.sms'))->assertOk()->assertSeeLivewire(SmsPasswordReset::class);
});

test('a signed-in user cannot open the sms reset page', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('password.sms'))->assertRedirect(route('dashboard'));
});

test('sending a code shows the same next step whether or not the number has an account', function (string $phone): void {
    User::factory()->create(['phone' => '+60123456789']);

    Livewire::test(SmsPasswordReset::class)
        ->set('phone', $phone)
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('showingCodeStep', true)
        ->assertSee(__('auth/sms-password-reset.sent'));
})->with(['a number of a user' => ['+60123456789'], 'an unknown number' => ['+60198765432']]);

test('a phone number in local format is refused', function (): void {
    Livewire::test(SmsPasswordReset::class)
        ->set('phone', '0123456789')
        ->call('send')
        ->assertHasErrors(['phone' => PhoneNumber::class])
        ->assertSet('showingCodeStep', false);
});

test('asking again too soon says when to try again', function (): void {
    Livewire::test(SmsPasswordReset::class)
        ->set('phone', '+60123456789')
        ->call('send')
        ->call('send')
        ->assertHasErrors('phone')
        ->assertSee('Too many codes. Try again in');
});

test('a locked number says when to try again', function (): void {
    lockSmsPasswordReset('+60123456789');

    Livewire::test(SmsPasswordReset::class)
        ->set('phone', '+60123456789')
        ->call('send')
        ->assertHasErrors('phone')
        ->assertSet('showingCodeStep', false)
        ->assertSee('Too many wrong codes for this number. Try again in 1 day, or reset your password by email.');
});

test('a number that locks during the code step says when to try again', function (): void {
    $component = Livewire::test(SmsPasswordReset::class)->set('phone', '+60123456789')->call('send');
    lockSmsPasswordReset('+60123456789');

    $component->set('code', '000000')
        ->call('confirm')
        ->assertHasErrors('code')
        ->assertSee('Too many wrong codes for this number.');
});

test('a wrong code shows an error', function (): void {
    Notification::fake();
    $user = User::factory()->create(['phone' => '+60123456789']);
    $component = Livewire::test(SmsPasswordReset::class)->set('phone', '+60123456789')->call('send');
    $code = Notification::sent($user, PasswordResetCode::class)->first()->code;

    $component->set('code', wrongSmsCode($code))->call('confirm')->assertHasErrors(['code' => 'The code is wrong or has expired.']);
});

test('a user can reset the password with an sms code', function (): void {
    Notification::fake();
    $user = User::factory()->create(['phone' => '+60123456789']);
    $component = Livewire::test(SmsPasswordReset::class)->set('phone', '+60123456789')->call('send');
    $code = Notification::sent($user, PasswordResetCode::class)->first()->code;

    $redirect = $component->set('code', $code)->call('confirm')->effects['redirect'];
    $token = str($redirect)->after('/reset-password/')->before('?')->value();

    expect($redirect)->toContain('email='.urlencode($user->email));

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('login', absolute: false));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    Notification::assertSentTo($user, SmsPasswordResetStarted::class);
    Notification::assertSentTo($user, PasswordWasReset::class);
});
