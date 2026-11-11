<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Actions\TrackMail;
use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\Shared\Mail\Support\SensitiveValues;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Mime\Email;

test('another try reuses the links of the first try', function (): void {
    $mail = Mail::factory()->create();

    TrackMail::make()->execute($mail, new Email()->html('<a href="https://example.org/a">a</a>'), new SensitiveValues([]));
    $token = $mail->links()->sole()->token;
    TrackMail::make()->execute($mail, new Email()->html('<a href="https://example.org/a">a</a>'), new SensitiveValues([]));

    expect($mail->links()->sole()->token)->toBe($token);
});

test('a link that holds an encoded sensitive value is not wrapped', function (): void {
    $mail = Mail::factory()->create();
    $email = new Email()->html('<a href="https://example.org/verify?code='.rawurlencode('ab+c/=').'">verify</a>');

    TrackMail::make()->execute($mail, $email, new SensitiveValues(['ab+c/=']));

    $this->assertDatabaseEmpty(MailLink::class);
    expect($email->getHtmlBody())->toContain('https://example.org/verify?code=ab%2Bc%2F%3D');
});

test('a link that holds a form-encoded sensitive value is not wrapped', function (): void {
    $mail = Mail::factory()->create();
    $email = new Email()->text('Verify at https://example.org/verify?code='.urlencode('ab c'));

    TrackMail::make()->execute($mail, $email, new SensitiveValues(['ab c']));

    $this->assertDatabaseEmpty(MailLink::class);
});

test('a link to a signed-in page needs sign-in even when the app has a fallback route', function (): void {
    Route::fallback(fn (): string => 'missing');
    $mail = Mail::factory()->create();

    TrackMail::make()->execute($mail, new Email()->html('<a href="'.route('settings').'">settings</a>'), new SensitiveValues([]));

    expect($mail->links()->sole()->requires_auth)->toBeTrue();
});

test('a text link keeps the punctuation after it out of its target', function (): void {
    $mail = Mail::factory()->create();
    $email = new Email()->text('See https://example.org/page.');

    TrackMail::make()->execute($mail, $email, new SensitiveValues([]));

    $link = $mail->links()->sole();
    expect($link->url)->toBe('https://example.org/page')
        ->and($email->getTextBody())->toBe('See '.route('mail.link', $link->token).'.');
});

test('only the href attribute of a link is wrapped', function (): void {
    $mail = Mail::factory()->create();
    $email = new Email()->html('<a data-href="https://example.org/data" href="https://example.org/real">a</a>');

    TrackMail::make()->execute($mail, $email, new SensitiveValues([]));

    expect($mail->links()->sole()->url)->toBe('https://example.org/real')
        ->and($email->getHtmlBody())->toContain('data-href="https://example.org/data"');
});

test('a link that is already a tracking link is not wrapped again', function (): void {
    $mail = Mail::factory()->create();

    TrackMail::make()->execute($mail, new Email()->html('<a href="'.route('mail.link', 'existing').'">a</a>'), new SensitiveValues([]));

    $this->assertDatabaseEmpty(MailLink::class);
});
