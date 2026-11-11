<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Enums\MailStatus;
use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Tests\Fixtures\Notifications\LetterIssued;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->user = User::factory()->create();
});

test('viewing emails needs system.logs.mail.view', function (): void {
    $mail = Mail::factory()->create();

    expect($this->user->can('viewAny', Mail::class))->toBeFalse()
        ->and($this->user->can('view', $mail))->toBeFalse();

    grantPermissions($this->user, 'system.logs.mail.view');

    expect($this->user->refresh()->can('viewAny', Mail::class))->toBeTrue()
        ->and($this->user->can('view', $mail))->toBeTrue();
});

test('resending needs system.logs.mail.resend and an email that can be rebuilt', function (): void {
    User::factory()->create()->notify(new LetterIssued);
    $resendable = Mail::query()->sole();
    $plain = Mail::factory()->create();

    expect($this->user->can('resend', $resendable))->toBeFalse();

    grantPermissions($this->user, 'system.logs.mail.resend');

    expect($this->user->refresh()->can('resend', $resendable))->toBeTrue()
        ->and($this->user->can('resend', $plain))->toBeFalse();
});

test('an email that is still sending cannot be resent', function (): void {
    User::factory()->create()->notify(new LetterIssued);
    $mail = Mail::query()->sole();
    $mail->update(['status' => MailStatus::Sending]);
    grantPermissions($this->user, 'system.logs.mail.resend');

    expect($this->user->refresh()->can('resend', $mail))->toBeFalse();
});

test('an email that is stuck sending, because its job was lost, can be resent', function (): void {
    User::factory()->create()->notify(new LetterIssued);
    $mail = Mail::query()->sole();
    $mail->update(['status' => MailStatus::Sending]);
    grantPermissions($this->user, 'system.logs.mail.resend');

    $this->travel(Mail::STUCK_AFTER_MINUTES + 1)->minutes();

    expect($this->user->refresh()->can('resend', $mail))->toBeTrue();
});

test('an email whose notification class no longer exists cannot be resent', function (): void {
    $mail = Mail::factory()->create(['payload' => 'O:24:"App\\Notifications\\Removed":0:{}']);
    grantPermissions($this->user, 'system.logs.mail.resend');

    expect($this->user->refresh()->can('resend', $mail))->toBeFalse();
});
