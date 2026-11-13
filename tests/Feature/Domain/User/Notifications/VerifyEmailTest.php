<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\User\Models\User;

test('the message log keeps no signed link and no tracking for a verification email', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);

    config(['mail.tracking' => true, 'mail.intercept.enabled' => false]);
    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();

    $message = Message::query()->sole();
    expect($message->html_body)->not->toContain('signature=')
        ->and($message->text_body)->not->toContain('signature=')
        ->and($message->html_body)->toContain('[hidden]')
        ->and($message->payload)->toBeNull()
        ->and($message->tracked)->toBeFalse();
    $this->assertDatabaseEmpty(MailLink::class);
});
