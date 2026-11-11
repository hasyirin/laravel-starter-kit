<?php

declare(strict_types=1);

use App\Domain\Shared\Sms\Rules\AllowedCallingCode;
use Illuminate\Support\Facades\Validator;

test('the rule accepts only a number with a calling code that the app sends sms to', function (mixed $phone, bool $passes): void {
    config(['system.sms.calling_codes' => ['+60']]);

    expect(Validator::make(['phone' => $phone], ['phone' => [new AllowedCallingCode]])->passes())->toBe($passes);
})->with([
    'a calling code in the list' => ['+60123456789', true],
    'a calling code outside the list' => ['+6591234567', false],
    'a value that is not text' => [60123456789, false],
]);

test('the rule says that the app does not send sms to the calling code', function (): void {
    $validator = Validator::make(['phone' => '+6591234567'], ['phone' => [new AllowedCallingCode]]);

    expect($validator->errors()->first('phone'))->toBe('The app does not send SMS to this calling code.');
});
