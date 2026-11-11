<?php

declare(strict_types=1);

use App\Domain\User\Rules\PhoneNumber;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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

test('a phone number parameter is marked sensitive, so a stack trace does not show the number', function (): void {
    $unmarked = collect(iterator_to_array(Finder::create()->files()->in(app_path())->name('*.php')))
        ->map(fn (SplFileInfo $file): string => 'App\\'.str($file->getRelativePathname())->replace(['/', '.php'], ['\\', ''])->value())
        ->flatMap(function (string $class): array {
            if (! class_exists($class) && ! interface_exists($class) && ! trait_exists($class)) {
                return [];
            }

            return new ReflectionClass($class)->getMethods();
        })
        ->filter(fn (ReflectionMethod $method): bool => str_starts_with($method->class, 'App\\'))
        ->flatMap(fn (ReflectionMethod $method): array => collect($method->getParameters())
            ->filter(fn (ReflectionParameter $parameter): bool => $parameter->getName() === 'phone' && $parameter->getAttributes(SensitiveParameter::class) === [])
            ->map(fn (): string => "{$method->class}::{$method->getName()}()")
            ->all())
        ->unique()
        ->values();

    expect($unmarked)->toBeEmpty("Mark the phone number with #[SensitiveParameter] in {$unmarked->implode(', ')}.");
});
