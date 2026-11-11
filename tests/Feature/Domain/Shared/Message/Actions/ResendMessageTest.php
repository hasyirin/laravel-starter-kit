<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Actions\ResendMessage;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Notification;
use Tests\Fixtures\Notifications\LetterIssued;

test('a resend makes a new row that links to the original', function (): void {
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $user->notify(new LetterIssued(cc: ['officer@example.com']));

    $original = Message::query()->sole();

    ResendMessage::make()->execute($original);

    $resent = Message::query()->whereKeyNot($original->id)->sole();
    expect($resent->resend_of_id)->toBe($original->id)
        ->and($resent->recipient_address)->toBe('aisyah@example.com')
        ->and($resent->cc)->toBe([['address' => 'officer@example.com', 'name' => '']])
        ->and($resent->notifiable->is($user))->toBeTrue();
});

test('a resend to a new address changes only the to recipient, and the user is no longer its recipient', function (): void {
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $user->notify(new LetterIssued(cc: ['officer@example.com']));

    $original = Message::query()->sole();

    ResendMessage::make()->execute($original, 'aisyah.new@example.com');

    $resent = Message::query()->whereKeyNot($original->id)->sole();
    expect($resent->recipient_address)->toBe('aisyah.new@example.com')
        ->and($resent->cc)->toBe([['address' => 'officer@example.com', 'name' => '']])
        ->and($resent->notifiable)->toBeNull()
        ->and($resent->isRecipient($user))->toBeFalse();
});

test('a resend of a resent email goes to the address of that email', function (): void {
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $user->notify(new LetterIssued);
    ResendMessage::make()->execute(Message::query()->sole(), 'aisyah.new@example.com');
    $resent = Message::query()->latest('id')->first();

    ResendMessage::make()->execute($resent);

    expect(Message::query()->latest('id')->first()->recipient_address)->toBe('aisyah.new@example.com');
});

test('a resend after the user changed the email goes to the address of the row', function (): void {
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $user->notify(new LetterIssued);
    $user->update(['email' => 'aisyah.changed@example.com']);

    ResendMessage::make()->execute(Message::query()->sole());

    expect(Message::query()->latest('id')->first()->recipient_address)->toBe('aisyah@example.com');
});

test('a resend of an email to an address without an account goes to that address', function (): void {
    Notification::route('mail', 'guest@example.com')->notify(new LetterIssued);
    $original = Message::query()->sole();

    ResendMessage::make()->execute($original);

    expect(Message::query()->whereKeyNot($original->id)->sole()->recipient_address)->toBe('guest@example.com');
});

test('an email with sensitive values cannot be resent', function (): void {
    User::factory()->create()->notify(new LetterIssued(secret: 'secret'));

    ResendMessage::make()->execute(Message::query()->sole());
})->throws(LogicException::class, 'cannot be resent');
