<?php

declare(strict_types=1);

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\VerifyEmail;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Notification;

test('deleting a user soft deletes it', function (): void {
    $user = User::factory()->create();

    $user->delete();

    expect(User::query()->find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id)?->deleted_at)->not->toBeNull();
});

test('a deleted user cannot log in', function (): void {
    $user = User::factory()->create();
    $user->delete();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->assertGuest();
});

test('two users cannot share an email', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);
    User::factory()->create(['email' => 'taken@example.com']);
})->throws(UniqueConstraintViolationException::class);

test('a deleted user does not block a new user with the same email', function (): void {
    User::factory()->create(['email' => 'taken@example.com'])->delete();
    User::factory()->create(['email' => 'taken@example.com'])->delete();

    $user = User::factory()->create(['email' => 'taken@example.com']);

    expect(User::withTrashed()->where('email', 'taken@example.com')->count())->toBe(3)
        ->and(User::query()->where('email', 'taken@example.com')->sole()->id)->toBe($user->id);
});

test('a deleted user cannot be restored while another user has the same email', function (): void {
    $deleted = User::factory()->create(['email' => 'taken@example.com']);
    $deleted->delete();
    User::factory()->create(['email' => 'taken@example.com']);

    $deleted->restore();
})->throws(UniqueConstraintViolationException::class);

test('force deleting a user deletes their profiles', function (): void {
    $profile = Profile::factory()->create();

    $profile->user->forceDelete();

    $this->assertModelMissing($profile);
});

test('a phone number is verified once it has a verification time', function (): void {
    $user = User::factory()->unverifiedPhone()->create();

    expect($user->hasVerifiedPhone())->toBeFalse();

    $user->markPhoneAsVerified();

    expect($user->fresh()->hasVerifiedPhone())->toBeTrue();
});

test('sms to a user goes to their phone number', function (): void {
    $user = User::factory()->create(['phone' => '+60123456789']);

    expect($user->routeNotificationFor('sms'))->toBe('+60123456789')
        ->and($user->getPhoneForVerification())->toBe('+60123456789');
});

test('two users cannot share a phone number', function (): void {
    User::factory()->create(['phone' => '+60123456789']);
    User::factory()->create(['phone' => '+60123456789']);
})->throws(UniqueConstraintViolationException::class);

test('a deleted user does not block a new user with the same phone number', function (): void {
    User::factory()->create(['phone' => '+60123456789'])->delete();

    $user = User::factory()->create(['phone' => '+60123456789']);

    expect(User::query()->where('phone', '+60123456789')->sole()->id)->toBe($user->id);
});

test('many users can have no phone number', function (): void {
    User::factory()->withoutPhone()->count(2)->create();

    expect(User::query()->whereNull('phone')->count())->toBe(2);
});

test('a message to a user uses the language of that user', function (): void {
    Notification::fake();
    $user = User::factory()->create(['language' => 'ms']);

    $user->notify(new VerifyEmail);

    Notification::assertSentTo($user, VerifyEmail::class, fn (VerifyEmail $notification, array $channels, User $notifiable, ?string $locale): bool => $locale === 'ms');
});

test('a message to a user without a listed language uses the language of the app, not of the sender', function (?string $language): void {
    config(['system.languages' => ['en' => 'English']]);
    app()->setLocale('ms');

    expect(User::factory()->make(['language' => $language])->preferredLocale())->toBe('en');
})->with([
    'no language' => [null],
    'an unlisted language' => ['ms'],
]);
