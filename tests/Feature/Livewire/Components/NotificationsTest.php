<?php

declare(strict_types=1);

use App\Domain\User\Models\User;

test('a page prints no broadcast channel while broadcasting is off', function (?string $connection): void {
    config([
        'broadcasting.connections.quiet' => ['driver' => 'log'],
        'broadcasting.default' => $connection,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('App.Domain.User.Models.User.', false)
        ->assertDontSee('broadcastChannel', false);
})->with([
    'the log connection' => 'log',
    'the null connection' => 'null',
    'no connection' => null,
    'a custom connection with the log driver' => 'quiet',
    'an empty connection' => '',
    'a connection that does not exist' => 'missing',
]);

test('a page prints the broadcast channel when broadcasting is on', function (): void {
    config(['broadcasting.default' => 'pusher']);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee("App.Domain.User.Models.User.{$user->id}", false);
});
