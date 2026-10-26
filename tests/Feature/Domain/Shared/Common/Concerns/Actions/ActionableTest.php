<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Tests\Fixtures\Actions\ShoutText;

test('make() resolves the action from the container', function (): void {
    $action = new ShoutText;
    app()->instance(ShoutText::class, $action);

    expect(ShoutText::make())->toBe($action)
        ->and(ShoutText::make()->execute('leak'))->toBe('LEAK');
});

test('every action uses the actionable trait and runs with execute(), except that a Fortify action keeps its contract method', function (): void {
    $actions = collect(iterator_to_array(Finder::create()->files()->in(app_path('Domain'))->path('#(^|/)Actions/#')->notPath('#(^|/)Concerns/#')->name('*.php')))
        ->map(fn (SplFileInfo $file): string => 'App\\Domain\\'.str($file->getRelativePathname())->replace(['/', '.php'], ['\\', ''])->value())
        ->values();

    expect($actions)->not->toBeEmpty();

    $actions->each(function (string $action): void {
        expect(Actionable::class)->toBeIn(class_uses_recursive($action), "{$action} does not use the Actionable trait.")
            ->and(str_contains($action, '\\Fortify\\') || method_exists($action, 'execute'))->toBeTrue("{$action} has no execute() method.");
    });
});
