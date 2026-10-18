<?php

use Illuminate\Support\Collection;
use Tests\Fixtures\Enums\Priority;
use Tests\Fixtures\Enums\Status;

test('options map each value to its label', function (): void {
    expect(Status::options()->all())->toBe([
        'draft' => 'Draft',
        'in_review' => 'In Review',
        'published' => 'Published',
    ]);
});

test('values list the backing values of int-backed enums', function (): void {
    expect(Priority::values()->all())->toBe([1, 2])
        ->and(Priority::options()->all())->toBe([1 => '1', 2 => '2']);
});

test('is matches a case, a value, an array, or an arrayable', function (): void {
    expect(Status::Draft->is(Status::Draft))->toBeTrue()
        ->and(Status::Draft->is('draft'))->toBeTrue()
        ->and(Status::Draft->is([Status::Published, 'draft']))->toBeTrue()
        ->and(Status::Draft->is(Collection::make(['in_review', Status::Published])))->toBeFalse()
        ->and(Priority::Low->is(1))->toBeTrue()
        ->and(Priority::Low->is([Priority::High]))->toBeFalse();
});

test('is compares values strictly', function (): void {
    expect(Priority::Low->is('1'))->toBeFalse();
});

test('not is the inverse of is', function (): void {
    expect(Status::Draft->not('published'))->toBeTrue()
        ->and(Status::Draft->not(Status::Draft))->toBeFalse();
});

test('string helpers transform the value', function (): void {
    expect(Status::InReview->camel())->toBe('inReview')
        ->and(Status::InReview->studly())->toBe('InReview')
        ->and(Status::InReview->headline())->toBe('In Review')
        ->and(Status::Draft->plural())->toBe('drafts')
        ->and(Status::InReview->snake())->toBe('in_review')
        ->and(Priority::High->headline())->toBe('2');
});

test('dd does nothing in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    Status::Draft->dd();

    expect(app()->isProduction())->toBeTrue();
});
