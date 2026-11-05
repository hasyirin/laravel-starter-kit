<?php

declare(strict_types=1);

use App\Filament\Actions\CreateAction;

test('a create action needs the name of its record as its label', function (): void {
    expect(fn (): mixed => CreateAction::make()->getLabel())->toThrow(LogicException::class);
});

test('a create action shows the name of its record', function (): void {
    expect(CreateAction::make()->label('User')->getLabel())->toBe('User');
});
