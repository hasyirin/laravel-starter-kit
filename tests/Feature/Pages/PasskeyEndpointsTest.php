<?php

declare(strict_types=1);

use App\Domain\User\Models\User;

test('guests can read the passkey endpoints', function (): void {
    $response = $this->get(route('well-known.passkeys'));

    $response->assertOk()->assertExactJson([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
});

test('signed-in users can read the passkey endpoints', function (): void {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('well-known.passkeys'));

    $response->assertOk()->assertExactJson([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
});
