<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\User\Models\User;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;

test('the mail log keeps no reset token and no tracking for a password reset email', function (): void {
    config(['mail.tracking' => true, 'mail.intercept.enabled' => false]);
    $user = User::factory()->create();

    $user->sendPasswordResetNotification('reset-token-1234567890');

    $mail = Mail::query()->sole();
    expect($mail->html_body)->not->toContain('reset-token-1234567890')
        ->and($mail->text_body)->not->toContain('reset-token-1234567890')
        ->and($mail->html_body)->toContain('[hidden]')
        ->and($mail->payload)->toBeNull()
        ->and($mail->tracked)->toBeFalse();
    $this->assertDatabaseEmpty(MailLink::class);
});

test('the password reset job is encrypted on the queue', function (): void {
    Queue::fake([SendQueuedNotifications::class]);

    User::factory()->create()->sendPasswordResetNotification('reset-token-1234567890');

    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->shouldBeEncrypted);
});
