<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\User\Models\User;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;

test('the message log keeps no reset token and no tracking for a password reset email', function (): void {
    $this->skipUnlessFeature(Feature::PasswordReset);

    config(['mail.tracking' => true, 'mail.intercept.enabled' => false]);
    $user = User::factory()->create();

    $user->sendPasswordResetNotification('reset-token-1234567890');

    $message = Message::query()->sole();
    expect($message->html_body)->not->toContain('reset-token-1234567890')
        ->and($message->text_body)->not->toContain('reset-token-1234567890')
        ->and($message->html_body)->toContain('[hidden]')
        ->and($message->payload)->toBeNull()
        ->and($message->tracked)->toBeFalse();
    $this->assertDatabaseEmpty(MailLink::class);
});

test('the password reset job is encrypted on the queue', function (): void {
    Queue::fake([SendQueuedNotifications::class]);

    User::factory()->create()->sendPasswordResetNotification('reset-token-1234567890');

    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->shouldBeEncrypted);
});
