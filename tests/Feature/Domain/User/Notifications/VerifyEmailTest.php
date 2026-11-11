<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\User\Models\User;

test('the mail log keeps no signed link and no tracking for a verification email', function (): void {
    config(['mail.tracking' => true, 'mail.intercept.enabled' => false]);
    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();

    $mail = Mail::query()->sole();
    expect($mail->html_body)->not->toContain('signature=')
        ->and($mail->text_body)->not->toContain('signature=')
        ->and($mail->html_body)->toContain('[hidden]')
        ->and($mail->payload)->toBeNull()
        ->and($mail->tracked)->toBeFalse();
    $this->assertDatabaseEmpty(MailLink::class);
});
