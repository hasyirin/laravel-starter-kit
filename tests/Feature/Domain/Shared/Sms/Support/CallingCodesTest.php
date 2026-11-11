<?php

declare(strict_types=1);

use App\Domain\Shared\Sms\Support\CallingCodes;

test('a number is allowed only when it starts with a configured calling code', function (array $codes, string $phone, bool $allowed): void {
    config(['system.sms.calling_codes' => $codes]);

    expect(CallingCodes::allows($phone))->toBe($allowed);
})->with([
    'a Malaysian number' => [['+60'], '+60123456789', true],
    'a Singapore number' => [['+60'], '+6591234567', false],
    'a second configured code' => [['+60', '+65'], '+6591234567', true],
]);
