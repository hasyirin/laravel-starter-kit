<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\Impersonation\StartImpersonation;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use Illuminate\Auth\Events\Logout;

test('a sign-out records one sign-out entry with the user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    expect(SignInEntry::query()->sole()->only('event', 'result', 'method', 'user_id', 'impersonator_id'))->toBe([
        'event' => SignInEvent::SignOut,
        'result' => null,
        'method' => null,
        'user_id' => $user->id,
        'impersonator_id' => null,
    ]);
});

test('a sign-out during an impersonation records the impersonated user with the impersonator', function (): void {
    $this->seed(PermissionSeeder::class);
    $impersonator = User::factory()->create();
    $user = User::factory()->create();
    grantPermissions($impersonator, 'internal.controls.users.impersonate', 'internal.controls.users.update');
    $this->actingAs($impersonator);
    StartImpersonation::make()->execute($impersonator, $user, 'https://example.test/start');
    auth()->forgetGuards();
    context()->flush();

    $this->post(route('logout'));

    $this->assertGuest();
    expect(SignInEntry::query()->where('event', SignInEvent::SignOut)->sole()->only('user_id', 'impersonator_id'))->toBe([
        'user_id' => $user->id,
        'impersonator_id' => $impersonator->id,
    ]);
});

test('a sign-out with no user records nothing', function (): void {
    event(new Logout('web', null));

    expect(SignInEntry::query()->count())->toBe(0);
});

test('a sign-out on another guard than the Fortify guard records nothing', function (): void {
    event(new Logout('other', User::factory()->create()));

    expect(SignInEntry::query()->count())->toBe(0);
});
