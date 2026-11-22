<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Filament\Notifications\Notification;

test('the bell prints no broadcast channel while broadcasting is off', function (): void {
    config(['broadcasting.default' => null]);

    $user = User::factory()->create();

    Notification::make()
        ->title('Report ready')
        ->sendToDatabase($user);

    expect($user->notifications()->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('App.Domain.User.Models.User.', false)
        ->assertDontSee('broadcastChannel', false);
});

test('the bell prints the broadcast channel when broadcasting is on', function (): void {
    config(['broadcasting.default' => 'pusher']);

    $user = User::factory()->create();

    Notification::make()
        ->title('Report ready')
        ->sendToDatabase($user);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('database-notifications.sent', false)
        ->assertSee("App.Domain.User.Models.User.{$user->id}", false);
});
