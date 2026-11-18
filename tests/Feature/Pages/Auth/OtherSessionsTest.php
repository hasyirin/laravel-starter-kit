<?php

declare(strict_types=1);

use App\Domain\User\Actions\Management\UpdateUser;
use App\Domain\User\Actions\Security\ResetForgottenPassword;
use App\Domain\User\Actions\Security\UpdatePassword;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Placement;

test('a password change sends the other sessions of the user to the login page', function (Closure $changePassword): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $changePassword(User::query()->findOrFail($user->id));

    $this->actingAs($user->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
})->with([
    'an update in the settings' => [fn (User $user) => UpdatePassword::make()->execute($user, 'new-password')],
    'a staff edit on the user page' => [fn (User $user) => UpdateUser::make()->execute(
        actor: User::factory()->create(),
        user: $user,
        name: $user->name,
        email: $user->email,
        phone: $user->phone,
        password: 'new-password',
        placement: new Placement,
    )],
    'a reset of a forgotten password' => [fn (User $user) => ResetForgottenPassword::make()->reset($user, ['password' => 'new-password', 'password_confirmation' => 'new-password'])],
]);

test('an open page sends the user to the login page once their password changes elsewhere', function (): void {
    $user = User::factory()->create();
    $page = $this->actingAs($user)->get(route('dashboard'));
    UpdatePassword::make()->execute(User::query()->findOrFail($user->id), 'new-password');
    $this->actingAs($user->fresh());

    $response = callLivewireAction($page, 'pages.app.dashboard', 'refresh');

    $response->assertRedirect(route('login'));
});
