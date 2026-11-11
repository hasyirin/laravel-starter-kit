<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Enums\MailStatus;
use App\Domain\Shared\Mail\Jobs\ResendMail;
use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\Fixtures\Notifications\LetterIssued;

test('the resend job keeps the notification encrypted on the queue', function (): void {
    config(['mail.queue_connection' => 'database']);

    dispatch(new ResendMail(User::factory()->create(), new LetterIssued));

    expect(DB::table('jobs')->sole()->payload)->not->toContain('LetterIssued');
});

test('the resend job marks its row as failed after the last try', function (): void {
    $notification = new LetterIssued;
    $notification->id = 'f3b1b8a2-1d8e-4a0e-9a57-2a7c3f6b0c13';

    $mail = Mail::factory()->sending()->create(['notification_id' => $notification->id]);

    new ResendMail(User::factory()->create(), $notification)->failed(new TransportException('Connection could not be established.'));

    expect($mail->refresh()->status)->toBe(MailStatus::Failed);
});
