<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Tests\Fixtures\Notifications\LetterIssued;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->user = User::factory()->create();
});

test('viewing emails needs system.logs.messages.view', function (): void {
    $message = Message::factory()->create();

    expect($this->user->can('viewAny', Message::class))->toBeFalse()
        ->and($this->user->can('view', $message))->toBeFalse();

    grantPermissions($this->user, 'system.logs.messages.view');

    expect($this->user->refresh()->can('viewAny', Message::class))->toBeTrue()
        ->and($this->user->can('view', $message))->toBeTrue();
});

test('resending needs system.logs.messages.resend and an email that can be rebuilt', function (): void {
    User::factory()->create()->notify(new LetterIssued);
    $resendable = Message::query()->sole();
    $plain = Message::factory()->create();

    expect($this->user->can('resend', $resendable))->toBeFalse();

    grantPermissions($this->user, 'system.logs.messages.resend');

    expect($this->user->refresh()->can('resend', $resendable))->toBeTrue()
        ->and($this->user->can('resend', $plain))->toBeFalse();
});

test('an email that is still sending cannot be resent', function (): void {
    User::factory()->create()->notify(new LetterIssued);
    $message = Message::query()->sole();
    $message->update(['status' => MessageStatus::Sending]);
    grantPermissions($this->user, 'system.logs.messages.resend');

    expect($this->user->refresh()->can('resend', $message))->toBeFalse();
});

test('an email that is stuck sending, because its job was lost, can be resent', function (): void {
    User::factory()->create()->notify(new LetterIssued);
    $message = Message::query()->sole();
    $message->update(['status' => MessageStatus::Sending]);
    grantPermissions($this->user, 'system.logs.messages.resend');

    $this->travel(Message::STUCK_AFTER_MINUTES + 1)->minutes();

    expect($this->user->refresh()->can('resend', $message))->toBeTrue();
});

test('an email whose notification class no longer exists cannot be resent', function (): void {
    $message = Message::factory()->create(['payload' => 'O:24:"App\\Notifications\\Removed":0:{}']);
    grantPermissions($this->user, 'system.logs.messages.resend');

    expect($this->user->refresh()->can('resend', $message))->toBeFalse();
});
