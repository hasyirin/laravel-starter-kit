<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Models\Mail;
use Carbon\CarbonImmutable;

test('the pixel records the first open and counts every open', function (): void {
    $mail = Mail::factory()->create(['tracked' => true]);
    $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00:00'));
    $this->get(route('mail.open', $mail->tracking_token));
    $this->travelTo(CarbonImmutable::parse('2026-11-10 10:00:00'));

    $response = $this->get(route('mail.open', $mail->tracking_token));

    $response->assertOk()->assertHeader('Content-Type', 'image/gif');
    expect($mail->refresh()->opened_at?->toDateTimeString())->toBe('2026-11-10 09:00:00')
        ->and($mail->open_count)->toBe(2);
});

test('the pixel records nothing for an email without tracking', function (): void {
    $mail = Mail::factory()->create(['tracked' => false]);

    $this->get(route('mail.open', $mail->tracking_token))->assertOk();

    expect($mail->refresh()->opened_at)->toBeNull();
});

test('the pixel answers an unknown token with the same image', function (): void {
    $response = $this->get(route('mail.open', 'unknown'));

    $response->assertOk()->assertHeader('Content-Type', 'image/gif');
});

test('the pixel limits repeated loads of one email', function (): void {
    $mail = Mail::factory()->create(['tracked' => true]);

    foreach (range(1, 30) as $load) {
        $this->get(route('mail.open', $mail->tracking_token))->assertOk();
    }

    $this->get(route('mail.open', $mail->tracking_token))->assertTooManyRequests();
    expect($mail->refresh()->open_count)->toBe(30);
});

test('the limit of one email does not limit another email', function (): void {
    $limited = Mail::factory()->create(['tracked' => true]);
    $other = Mail::factory()->create(['tracked' => true]);

    foreach (range(1, 30) as $load) {
        $this->get(route('mail.open', $limited->tracking_token));
    }

    $this->get(route('mail.open', $other->tracking_token))->assertOk();
});
