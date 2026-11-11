<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Enums\MessageFailureReason;
use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Message\Notifications\Notification;
use Illuminate\Queue\TimeoutExceededException;
use Tests\Fixtures\Notifications\LetterIssued;

test('the app-wide tracking switch is the default of a notification', function (): void {
    config(['mail.tracking' => false]);

    expect(new class extends Notification {}->tracked(new stdClass))->toBeFalse();
});

test('the last failed try marks the row as failed', function (): void {
    $notification = new LetterIssued;
    $notification->id = 'f3b1b8a2-1d8e-4a0e-9a57-2a7c3f6b0c12';

    $message = Message::factory()->sending()->create(['notification_id' => $notification->id]);

    $notification->failed(new TimeoutExceededException('The job has timed out.'));

    expect($message->refresh()->status)->toBe(MessageStatus::Failed)
        ->and($message->failure_reason)->toBe(MessageFailureReason::Timeout);
});
