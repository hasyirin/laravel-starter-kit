<?php

declare(strict_types=1);

use App\Domain\User\Rules\PhoneNumber;
use Illuminate\Support\Facades\Validator;

test('a phone number must be an international number', function (mixed $value, bool $passes): void {
    expect(Validator::make(['phone' => $value], ['phone' => [new PhoneNumber]])->passes())->toBe($passes);
})->with([
    'a Malaysian mobile number' => ['+60123456789', true],
    'a Singapore number' => ['+6591234567', true],
    'a local number' => ['0123456789', false],
    'a number with spaces' => ['+60 12-345 6789', false],
    'a number that starts with zero' => ['+0123456789', false],
    'a number that is too short' => ['+601234', false],
    'a number that is too long' => ['+6012345678901234', false],
    'a number with a trailing newline' => ["+60123456789\n", false],
]);

test('a phone number that is not international gets a message with an example', function (): void {
    $validator = Validator::make(['phone' => '0123456789'], ['phone' => [new PhoneNumber]]);

    expect($validator->errors()->first('phone'))->toBe('The phone number must be an international number, such as +60123456789.');
});
