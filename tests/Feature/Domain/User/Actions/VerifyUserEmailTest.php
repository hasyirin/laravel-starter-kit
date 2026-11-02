<?php

declare(strict_types=1);

use App\Domain\User\Actions\VerifyUserEmail;
use App\Domain\User\Models\User;

test('marking an email as verified sets the time', function (): void {
    $user = User::factory()->unverified()->create();

    VerifyUserEmail::make()->execute($user);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('marking a verified email keeps the time', function (): void {
    $verifiedAt = now()->subDay()->startOfSecond();
    $user = User::factory()->create(['email_verified_at' => $verifiedAt]);

    VerifyUserEmail::make()->execute($user);

    expect($user->fresh()->email_verified_at?->equalTo($verifiedAt))->toBeTrue();
});
