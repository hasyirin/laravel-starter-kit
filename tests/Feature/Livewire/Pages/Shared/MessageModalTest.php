<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Actions\ResendMessage;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Shared\MessageModal;
use Dom\HTMLDocument;
use Livewire\Livewire;
use Tests\Fixtures\Notifications\LetterIssued;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->viewer = User::factory()->create();
    grantPermissions($this->viewer, 'system.access', 'system.logs.messages.view');
    $this->actingAs($this->viewer);
});

function resendableMail(): Message
{
    User::factory()->create()->notify(new LetterIssued);

    return Message::query()->sole();
}

test('the modal shows the email, its failure reason, and its suggestion', function (): void {
    $message = Message::factory()->failed()->create(['subject' => 'Letter issued']);

    Livewire::test(MessageModal::class)
        ->call('show', id: $message->id)
        ->assertSee('Letter issued')
        ->assertSee(__('message/messages.reasons.connection_failed.label'))
        ->assertSee(__('message/messages.reasons.connection_failed.suggestion'));
});

test('the modal shows an email without a recipient', function (): void {
    $message = Message::factory()->create(['recipient_address' => null, 'subject' => 'Copy only']);

    Livewire::test(MessageModal::class)
        ->call('show', id: $message->id)
        ->assertOk()
        ->assertSee('Copy only');
});

test('a user with system.logs.messages.resend can resend an email to a new address', function (): void {
    grantPermissions($this->viewer, 'system.logs.messages.resend');
    $message = resendableMail();
    $this->mock(ResendMessage::class)->shouldReceive('execute')->once()->withArgs(fn (Message $resent, string $email): bool => $resent->is($message) && $email === 'new@example.com');

    Livewire::test(MessageModal::class)
        ->call('show', id: $message->id)
        ->set('email', 'new@example.com')
        ->call('resend')
        ->assertHasNoErrors();
});

test('a resend needs a valid address', function (string $email, string $rule): void {
    grantPermissions($this->viewer, 'system.logs.messages.resend');
    $message = resendableMail();

    Livewire::test(MessageModal::class)
        ->call('show', id: $message->id)
        ->set('email', $email)
        ->call('resend')
        ->assertHasErrors(['email' => $rule]);

    $this->assertDatabaseCount(Message::class, 1);
})->with([
    'empty' => ['', 'required'],
    'not an address' => ['not an address', 'email'],
    'longer than 255 characters' => [str_repeat('a', 244).'@example.com', 'max'],
]);

test('a user without system.logs.messages.resend cannot resend an email', function (): void {
    $message = resendableMail();

    Livewire::test(MessageModal::class)
        ->call('show', id: $message->id)
        ->assertDontSee(__('message/messages.resend_to'))
        ->call('resend')
        ->assertForbidden();

    $this->assertDatabaseCount(Message::class, 1);
});

test('the modal does not find an email that does not exist', function (): void {
    Livewire::test(MessageModal::class)
        ->call('show', id: 999)
        ->assertNotFound();
});

test('the modal escapes the subject and the recipient name', function (): void {
    $message = Message::factory()->create(['subject' => '<b>Subject</b>', 'recipient_name' => '<i>Name</i>']);

    Livewire::test(MessageModal::class)
        ->call('show', id: $message->id)
        ->assertSee('<b>Subject</b>')
        ->assertDontSeeHtml('<b>Subject</b>')
        ->assertDontSeeHtml('<i>Name</i>');
});

test('the modal needs system.logs.messages.view', function (): void {
    revokePermissions($this->viewer, 'system.logs.messages.view');

    Livewire::test(MessageModal::class)->assertForbidden();
});

test('the modal shows the email body inside a sandboxed frame without scripts', function (): void {
    $message = Message::factory()->create(['html_body' => '<p>Hello</p><script>alert(1)</script>']);

    $html = Livewire::test(MessageModal::class)->call('show', id: $message->id)->html();

    $iframe = HTMLDocument::createFromString('<!doctype html><body>'.$html, LIBXML_NOERROR)->querySelector('iframe');
    expect($iframe?->getAttribute('sandbox'))->toBe('')
        ->and($iframe?->getAttribute('srcdoc'))->toBe('<p>Hello</p><script>alert(1)</script>')
        ->and($html)->not->toContain('<script>alert(1)</script>');
});
