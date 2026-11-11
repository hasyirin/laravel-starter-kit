<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Jobs\ResendMessage;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\Fixtures\Notifications\LetterIssued;
use Tests\Fixtures\Notifications\LetterReady;

test('the resend job keeps the notification encrypted on the queue', function (): void {
    config(['mail.queue_connection' => 'database']);

    dispatch(new ResendMessage(User::factory()->create(), new LetterIssued, 'mail'));

    expect(DB::table('jobs')->sole()->payload)->not->toContain('LetterIssued');
});

test('the resend job marks its row as failed after the last try', function (): void {
    $notification = new LetterIssued;
    $notification->id = 'f3b1b8a2-1d8e-4a0e-9a57-2a7c3f6b0c13';

    $message = Message::factory()->sending()->create(['notification_id' => $notification->id]);

    new ResendMessage(User::factory()->create(), $notification, 'mail')->failed(new TransportException('Connection could not be established.'));

    expect($message->refresh()->status)->toBe(MessageStatus::Failed);
});

test('the resend job of an email goes on the mail queue connection, and the one of an sms on the default connection', function (): void {
    config(['mail.queue_connection' => 'database']);
    $user = User::factory()->create();

    expect(new ResendMessage($user, new LetterIssued, 'mail')->connection)->toBe('database')
        ->and(new ResendMessage($user, new LetterReady, 'sms')->connection)->toBeNull();
});
