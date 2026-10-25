<?php

declare(strict_types=1);

use App\Domain\User\Models\User;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('settings'));

    $response->assertRedirect(route('login'));
});

test('users are sent to their profile settings', function (): void {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('settings'));

    $response->assertRedirect()->assertHeader('Location', route('profile.edit', absolute: false));
});
