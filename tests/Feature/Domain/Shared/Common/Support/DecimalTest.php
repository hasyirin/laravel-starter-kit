<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Support\Decimal;

test('a decimal is formatted from its digits, rounded half away from zero', function (string|int|float $value, int $decimals, string $separator, string $comma, string $expected): void {
    expect(Decimal::format($value, $decimals, $separator, $comma))->toBe($expected);
})->with([
    'more digits than a float holds' => ['12345678901234567.89', 2, '.', ',', '12,345,678,901,234,567.89'],
    'a half rounds up' => ['1.005', 2, '.', ',', '1.01'],
    'a negative half rounds down' => ['-1.005', 2, '.', ',', '-1.01'],
    'a tiny negative becomes zero' => ['-0.001', 2, '.', ',', '0.00'],
    'an integer gets its decimals' => [5, 2, '.', ',', '5.00'],
    'no decimals' => ['1234.5', 0, '.', ',', '1,235'],
    'other separators' => ['1234.56', 2, ',', '.', '1.234,56'],
    'a space as the thousands separator' => ['1234567', 0, '.', ' ', '1 234 567'],
]);
