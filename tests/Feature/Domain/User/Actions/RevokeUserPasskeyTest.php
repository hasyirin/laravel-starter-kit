<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\RevokeUserPasskey;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Passkeys\Passkey;

function userPasskey(User $user): Passkey
{
    return $user->passkeys()->create([
        'name' => 'Laptop',
        'credential_id' => str()->random(32),
        'credential' => ['id' => 'credential'],
    ]);
}

test('an actor revokes a passkey of another user', function (): void {
    $user = User::factory()->create();
    $passkey = userPasskey($user);

    RevokeUserPasskey::make()->execute(User::factory()->create(), $user, $passkey->id);

    expect(Passkey::query()->find($passkey->id))->toBeNull();
});

test('an actor cannot revoke their own passkey here', function (): void {
    $actor = User::factory()->create();
    $passkey = userPasskey($actor);

    expect(fn () => RevokeUserPasskey::make()->execute($actor, $actor, $passkey->id))->toThrow(AuthorizationException::class);
    expect(Passkey::query()->find($passkey->id))->not->toBeNull();
});

test('a passkey of another user cannot be revoked', function (): void {
    $user = User::factory()->create();
    $passkey = userPasskey(User::factory()->create());

    expect(fn () => RevokeUserPasskey::make()->execute(User::factory()->create(), $user, $passkey->id))->toThrow(ModelNotFoundException::class);
    expect(Passkey::query()->find($passkey->id))->not->toBeNull();
});

test('a user without bypass-authorization cannot revoke a passkey of a user who has it', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'bypass-authorization');

    $passkey = userPasskey($user);

    expect(fn () => RevokeUserPasskey::make()->execute(User::factory()->create(), $user, $passkey->id))->toThrow(AuthorizationException::class);
    expect(Passkey::query()->find($passkey->id))->not->toBeNull();
});
