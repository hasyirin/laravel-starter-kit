<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\VerifyUserEmail;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('marking an email as verified sets the time', function (): void {
    $user = User::factory()->unverified()->create();

    VerifyUserEmail::make()->execute(User::factory()->create(), $user);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('marking a verified email keeps the time', function (): void {
    $verifiedAt = now()->subDay()->startOfSecond();
    $user = User::factory()->create(['email_verified_at' => $verifiedAt]);

    VerifyUserEmail::make()->execute(User::factory()->create(), $user);

    expect($user->fresh()->email_verified_at?->equalTo($verifiedAt))->toBeTrue();
});

test('a user without bypass-authorization cannot verify the email of a user who has it', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->unverified()->create();
    $user->givePermissionTo('bypass-authorization');

    expect(fn () => VerifyUserEmail::make()->execute(User::factory()->create(), $user))->toThrow(AuthorizationException::class);
    expect($user->fresh()->email_verified_at)->toBeNull();
});
