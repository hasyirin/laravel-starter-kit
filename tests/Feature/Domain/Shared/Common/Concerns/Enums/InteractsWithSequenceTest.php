<?php

declare(strict_types=1);

use Tests\Fixtures\Enums\Status;

test('next and previous step through the cases in order', function (): void {
    expect(Status::Draft->next())->toBe(Status::InReview)
        ->and(Status::InReview->next())->toBe(Status::Published)
        ->and(Status::Published->previous())->toBe(Status::InReview)
        ->and(Status::InReview->previous())->toBe(Status::Draft);
});

test('neither end wraps around', function (): void {
    expect(Status::Published->next())->toBeNull()
        ->and(Status::Draft->previous())->toBeNull();
});
