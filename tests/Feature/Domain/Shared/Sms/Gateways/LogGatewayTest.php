<?php

declare(strict_types=1);

use App\Domain\Shared\Sms\Gateways\LogGateway;
use Illuminate\Support\Facades\Log;

test('the log gateway writes each sms to the log', function (): void {
    Log::spy();

    new LogGateway()->send('+60123456789', 'Your code is 123456.');

    Log::shouldHaveReceived('info')->once()->with('SMS sent.', ['to' => '+60123456789', 'message' => 'Your code is 123456.']);
});

test('the log gateway refuses to send in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    expect(fn () => new LogGateway()->send('+60123456789', 'Hi'))
        ->toThrow(RuntimeException::class, 'The log SMS gateway cannot send SMS in production. Set SMS_GATEWAY to the gateway of the app.');
});
