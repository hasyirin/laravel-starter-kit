<?php

declare(strict_types=1);

use Filament\Support\Colors\Color;
use Tests\Fixtures\Enums\Priority;
use Tests\Fixtures\Enums\Status;

test('palette names resolve to the filament palette', function (): void {
    expect(Status::Published->getColor())->toBe(Color::Green)
        ->and(Priority::High->getColor())->toBe(Color::Red);
});

test('theme color names are kept', function (): void {
    expect(Status::InReview->getColor())->toBe('warning')
        ->and(Status::Draft->getColor())->toBe('gray');
});

test('palette arrays pass through', function (): void {
    expect(Priority::Low->getColor())->toBe(Color::Blue);
});
