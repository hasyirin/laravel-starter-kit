<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Actions\ResendMail;
use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Shared\MailModal;
use Dom\HTMLDocument;
use Livewire\Livewire;
use Tests\Fixtures\Notifications\LetterIssued;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->viewer = User::factory()->create();
    grantPermissions($this->viewer, 'system.access', 'system.logs.mail.view');
    $this->actingAs($this->viewer);
});

function resendableMail(): Mail
{
    User::factory()->create()->notify(new LetterIssued);

    return Mail::query()->sole();
}

test('the modal shows the email, its failure reason, and its suggestion', function (): void {
    $mail = Mail::factory()->failed()->create(['subject' => 'Letter issued']);

    Livewire::test(MailModal::class)
        ->call('show', id: $mail->id)
        ->assertSee('Letter issued')
        ->assertSee(__('mail/mails.reasons.connection_failed.label'))
        ->assertSee(__('mail/mails.reasons.connection_failed.suggestion'));
});

test('the modal shows an email without a recipient', function (): void {
    $mail = Mail::factory()->create(['recipient_email' => null, 'subject' => 'Copy only']);

    Livewire::test(MailModal::class)
        ->call('show', id: $mail->id)
        ->assertOk()
        ->assertSee('Copy only');
});

test('a user with system.logs.mail.resend can resend an email to a new address', function (): void {
    grantPermissions($this->viewer, 'system.logs.mail.resend');
    $mail = resendableMail();
    $this->mock(ResendMail::class)->shouldReceive('execute')->once()->withArgs(fn (Mail $resent, string $email): bool => $resent->is($mail) && $email === 'new@example.com');

    Livewire::test(MailModal::class)
        ->call('show', id: $mail->id)
        ->set('email', 'new@example.com')
        ->call('resend')
        ->assertHasNoErrors();
});

test('a resend needs a valid address', function (string $email, string $rule): void {
    grantPermissions($this->viewer, 'system.logs.mail.resend');
    $mail = resendableMail();

    Livewire::test(MailModal::class)
        ->call('show', id: $mail->id)
        ->set('email', $email)
        ->call('resend')
        ->assertHasErrors(['email' => $rule]);

    $this->assertDatabaseCount(Mail::class, 1);
})->with([
    'empty' => ['', 'required'],
    'not an address' => ['not an address', 'email'],
    'longer than 255 characters' => [str_repeat('a', 244).'@example.com', 'max'],
]);

test('a user without system.logs.mail.resend cannot resend an email', function (): void {
    $mail = resendableMail();

    Livewire::test(MailModal::class)
        ->call('show', id: $mail->id)
        ->assertDontSee(__('mail/mails.resend_to'))
        ->call('resend')
        ->assertForbidden();

    $this->assertDatabaseCount(Mail::class, 1);
});

test('the modal does not find an email that does not exist', function (): void {
    Livewire::test(MailModal::class)
        ->call('show', id: 999)
        ->assertNotFound();
});

test('the modal escapes the subject and the recipient name', function (): void {
    $mail = Mail::factory()->create(['subject' => '<b>Subject</b>', 'recipient_name' => '<i>Name</i>']);

    Livewire::test(MailModal::class)
        ->call('show', id: $mail->id)
        ->assertSee('<b>Subject</b>')
        ->assertDontSeeHtml('<b>Subject</b>')
        ->assertDontSeeHtml('<i>Name</i>');
});

test('the modal needs system.logs.mail.view', function (): void {
    revokePermissions($this->viewer, 'system.logs.mail.view');

    Livewire::test(MailModal::class)->assertForbidden();
});

test('the modal shows the email body inside a sandboxed frame without scripts', function (): void {
    $mail = Mail::factory()->create(['html_body' => '<p>Hello</p><script>alert(1)</script>']);

    $html = Livewire::test(MailModal::class)->call('show', id: $mail->id)->html();

    $iframe = HTMLDocument::createFromString('<!doctype html><body>'.$html, LIBXML_NOERROR)->querySelector('iframe');
    expect($iframe?->getAttribute('sandbox'))->toBe('')
        ->and($iframe?->getAttribute('srcdoc'))->toBe('<p>Hello</p><script>alert(1)</script>')
        ->and($html)->not->toContain('<script>alert(1)</script>');
});
