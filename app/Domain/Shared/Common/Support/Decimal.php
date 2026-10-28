<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Support;

use InvalidArgumentException;

class Decimal
{
    public static function format(string|int|float $value, int $decimals = 0, string $separator = '.', string $comma = ','): string
    {
        $number = (string) $value;

        throw_unless(is_numeric($number), InvalidArgumentException::class, "[{$number}] is not a number.");

        $half = bcdiv('5', bcpow('10', (string) ($decimals + 1)), $decimals + 1);
        $rounded = str_starts_with($number, '-') ? bcsub($number, $half, $decimals) : bcadd($number, $half, $decimals);

        [$whole, $fraction] = array_pad(explode('.', ltrim($rounded, '-')), 2, '');
        $sign = str_starts_with($rounded, '-') && trim($rounded, '-0.') !== '' ? '-' : '';
        $whole = (string) preg_replace('/\B(?=(\d{3})+(?!\d))/', $comma, $whole);

        return $sign.$whole.($decimals > 0 ? $separator.$fraction : '');
    }
}
