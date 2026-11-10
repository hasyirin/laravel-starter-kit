<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PhoneVerificationCodeSms;
use App\Domain\User\Support\Impersonation;
use App\Livewire\Auth\VerifyPhone;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('the phone verification page opens for a user with an unverified phone number', function (): void {
    $this->actingAs(User::factory()->unverifiedPhone()->create());

    $this->get(route('phone-verification.notice'))->assertOk()->assertSeeLivewire(VerifyPhone::class);
});

test('the phone verification page needs a verified email first', function (): void {
    $this->actingAs(User::factory()->unverified()->unverifiedPhone()->create());

    $this->get(route('phone-verification.notice'))->assertRedirect(route('verification.notice'));
});

test('opening the page sends a code', function (): void {
    Notification::fake();
    $this->actingAs($user = User::factory()->unverifiedPhone()->create(['phone' => '+60123456789']));

    Livewire::test(VerifyPhone::class)->assertSee('+60123456789');

    Notification::assertSentToTimes($user, PhoneVerificationCodeSms::class, 1);
});

test('opening the page again sends no code while the last one is unused', function (): void {
    Notification::fake();
    $this->actingAs($user = User::factory()->unverifiedPhone()->create());

    Livewire::test(VerifyPhone::class);
    $this->travel(5)->minutes();
    Livewire::test(VerifyPhone::class);

    Notification::assertSentToTimes($user, PhoneVerificationCodeSms::class, 1);
});

test('opening the page sends a new code once the last one expired', function (): void {
    Notification::fake();
    $this->actingAs($user = User::factory()->unverifiedPhone()->create());

    Livewire::test(VerifyPhone::class);
    $this->travel(10)->minutes();
    Livewire::test(VerifyPhone::class);

    Notification::assertSentToTimes($user, PhoneVerificationCodeSms::class, 2);
});

test('opening the page sends no code during impersonation', function (): void {
    Notification::fake();
    $this->actingAs(User::factory()->unverifiedPhone()->create());
    Impersonation::begin(User::factory()->create(), '/');

    Livewire::test(VerifyPhone::class);

    Notification::assertNothingSent();
});

test('the resend button sends a new code', function (): void {
    Notification::fake();
    $this->actingAs($user = User::factory()->unverifiedPhone()->create());
    $component = Livewire::test(VerifyPhone::class);
    $this->travel(60)->seconds();

    $component->call('send')->assertSee(__('auth/verify-phone.sent'));

    Notification::assertSentToTimes($user, PhoneVerificationCodeSms::class, 2);
});

test('resending too soon says when to try again', function (): void {
    Notification::fake();
    $this->actingAs($user = User::factory()->unverifiedPhone()->create());

    Livewire::test(VerifyPhone::class)->call('send')->assertSee('Too many codes. Try again in');

    Notification::assertSentToTimes($user, PhoneVerificationCodeSms::class, 1);
});

test('the right code verifies the phone number and leaves the page', function (): void {
    Notification::fake();
    $this->actingAs($user = User::factory()->unverifiedPhone()->create());
    $component = Livewire::test(VerifyPhone::class);
    $code = Notification::sent($user, PhoneVerificationCodeSms::class)->first()->code;

    $component->set('code', $code)->call('confirm')->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasVerifiedPhone())->toBeTrue();
});

test('a wrong code shows an error', function (): void {
    Notification::fake();
    $this->actingAs($user = User::factory()->unverifiedPhone()->create());
    $component = Livewire::test(VerifyPhone::class);
    $code = Notification::sent($user, PhoneVerificationCodeSms::class)->first()->code;

    $component->set('code', wrongSmsCode($code))->call('confirm')->assertHasErrors(['code' => 'The code is wrong or has expired.']);

    expect($user->fresh()->hasVerifiedPhone())->toBeFalse();
});

test('a verified phone number leaves the page at once', function (): void {
    Notification::fake();
    $this->actingAs(User::factory()->create());

    Livewire::test(VerifyPhone::class)->assertRedirect(route('dashboard'));

    Notification::assertNothingSent();
});

test('a user without a phone number is told to add one', function (): void {
    Notification::fake();
    $this->actingAs(User::factory()->withoutPhone()->create());

    Livewire::test(VerifyPhone::class)
        ->assertSee(__('auth/verify-phone.no_phone'))
        ->assertSee(route('user-information.edit'));

    Notification::assertNothingSent();
});

test('a number outside the calling codes gets no code', function (): void {
    Notification::fake();
    $this->actingAs(User::factory()->unverifiedPhone()->create(['phone' => '+6591234567']));

    Livewire::test(VerifyPhone::class)->assertSee(__('auth/verify-phone.not_covered', ['phone' => '+6591234567']));

    Notification::assertNothingSent();
});

test('a holder of bypass-authorization can still verify a phone number', function (): void {
    Notification::fake();
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->unverifiedPhone()->create();
    grantPermissions($user, 'bypass-authorization');
    $this->actingAs($user);

    Livewire::test(VerifyPhone::class)->assertSee(__('auth/verify-phone.submit'));

    Notification::assertSentToTimes($user, PhoneVerificationCodeSms::class, 1);
});
