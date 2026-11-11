<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Enums\MessageFailureReason;
use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Message\Notifications\Notification;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\User\Models\User;
use Illuminate\Queue\TimeoutExceededException;
use Tests\Fixtures\Gateways\FakeGateway;
use Tests\Fixtures\Notifications\LetterIssued;
use Tests\Fixtures\Notifications\LetterReady;

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

test('the last failed try of a queue job fails only the row of its own channel', function (): void {
    config(['queue.default' => 'database']);
    app()->instance(SmsGateway::class, new FakeGateway(failure: new RuntimeException('The provider is down.')));
    $user = User::factory()->create();
    $notification = new LetterReady;
    $notification->id = 'f3b1b8a2-1d8e-4a0e-9a57-2a7c3f6b0c13';
    $notification->tries = 1;

    $email = Message::factory()->sending()->create(['notification_id' => $notification->id]);
    $user->notify($notification);

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'default']);

    $sms = Message::query()->where('channel', 'sms')->sole();
    expect($sms->status)->toBe(MessageStatus::Failed)
        ->and($sms->error['message'])->toBe('The provider is down.')
        ->and($email->refresh()->status)->toBe(MessageStatus::Sending);
});

test('the last failed try of a queue job for the bell fails no row of the message log', function (): void {
    config(['queue.default' => 'database']);
    $notification = new LetterReady(channels: ['database']);
    $notification->id = 'f3b1b8a2-1d8e-4a0e-9a57-2a7c3f6b0c14';
    $notification->tries = 1;

    $email = Message::factory()->sending()->create(['notification_id' => $notification->id]);
    User::factory()->create()->notify($notification);

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'default']);

    $this->assertDatabaseCount('failed_jobs', 1);
    expect($email->refresh()->status)->toBe(MessageStatus::Sending);
});
