<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Models\Message;

test('a message names its channel with the label of the app, or with the name of the channel', function (string $channel, string $label): void {
    expect(Message::factory()->make(['channel' => $channel])->channel_label)->toBe($label);
})->with([
    'the email channel' => ['mail', 'Email'],
    'the sms channel' => ['sms', 'SMS'],
    'a channel with a short name' => ['pager', 'Pager'],
    'a channel class' => ['App\\Channels\\PagerChannel', 'Pager'],
    'a channel class with the word in the middle' => ['App\\Channels\\MultiChannelPush', 'Multi Channel Push'],
    'a class with the name Channel' => ['App\\Pager\\Channel', 'Channel'],
]);

test('the content of a message is the subject of an email and the text of each other channel', function (): void {
    expect(Message::factory()->make(['subject' => 'Letter issued', 'text_body' => 'The body.'])->content)->toBe('Letter issued')
        ->and(Message::factory()->make(['subject' => null, 'text_body' => 'The body.'])->content)->toBeNull()
        ->and(Message::factory()->sms()->make(['text_body' => 'Your letter is ready.'])->content)->toBe('Your letter is ready.');
});
