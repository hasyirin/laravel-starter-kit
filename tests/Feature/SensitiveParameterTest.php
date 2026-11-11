<?php

declare(strict_types=1);

use App\Domain\Shared\Sms\Contracts\SmsGateway;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

test('a parameter that holds a phone number, a password, a one-time code, a password reset token or an sms text is marked sensitive, so a stack trace does not show it', function (): void {
    $secret = fn (ReflectionMethod $method, ReflectionParameter $parameter): bool => match ($parameter->getName()) {
        'phone', 'password' => true,
        'code', 'token' => str_starts_with($method->class, 'App\\Domain\\User\\'),
        'input' => str_starts_with($method->class, 'App\\Domain\\User\\Actions\\Fortify\\'),
        'message' => is_a($method->class, SmsGateway::class, true),
        'address' => str_starts_with($method->class, 'App\\Domain\\Shared\\Message\\'),
        default => false,
    };

    $unmarked = collect(iterator_to_array(Finder::create()->files()->in(app_path())->name('*.php')))
        ->map(fn (SplFileInfo $file): string => 'App\\'.str($file->getRelativePathname())->replace(['/', '.php'], ['\\', ''])->value())
        ->flatMap(function (string $class): array {
            if (! class_exists($class) && ! interface_exists($class) && ! trait_exists($class)) {
                return [];
            }

            return new ReflectionClass($class)->getMethods();
        })
        ->filter(fn (ReflectionMethod $method): bool => str_starts_with((string) $method->getFileName(), app_path()))
        ->flatMap(fn (ReflectionMethod $method): array => collect($method->getParameters())
            ->filter(fn (ReflectionParameter $parameter): bool => $secret($method, $parameter) && $parameter->getAttributes(SensitiveParameter::class) === [])
            ->map(fn (ReflectionParameter $parameter): string => "\${$parameter->getName()} of {$method->class}::{$method->getName()}()")
            ->all())
        ->unique()
        ->values();

    expect($unmarked)->toBeEmpty("Mark {$unmarked->implode(', ')} with #[SensitiveParameter].");
});
