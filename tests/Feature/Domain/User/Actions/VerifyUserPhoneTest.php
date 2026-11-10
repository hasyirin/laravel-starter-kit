<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\VerifyUserPhone;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('marking a phone number as verified sets the time', function (): void {
    $user = User::factory()->unverifiedPhone()->create();

    VerifyUserPhone::make()->execute(User::factory()->create(), $user);

    expect($user->fresh()->phone_verified_at)->not->toBeNull();
});

test('marking a verified phone number keeps the time', function (): void {
    $verifiedAt = now()->subDay()->startOfSecond();
    $user = User::factory()->create(['phone_verified_at' => $verifiedAt]);

    VerifyUserPhone::make()->execute(User::factory()->create(), $user);

    expect($user->fresh()->phone_verified_at?->equalTo($verifiedAt))->toBeTrue();
});

test('a user without a phone number stays unverified', function (): void {
    $user = User::factory()->withoutPhone()->create();

    VerifyUserPhone::make()->execute(User::factory()->create(), $user);

    expect($user->fresh()->phone_verified_at)->toBeNull();
});

test('a user without bypass-authorization cannot verify the phone number of a user who has it', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->unverifiedPhone()->create();
    grantPermissions($user, 'bypass-authorization');

    expect(fn () => VerifyUserPhone::make()->execute(User::factory()->create(), $user))->toThrow(AuthorizationException::class);
    expect($user->fresh()->phone_verified_at)->toBeNull();
});
