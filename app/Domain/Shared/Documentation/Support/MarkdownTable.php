<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Support;

class MarkdownTable
{
    /**
     * @param  list<string>  $headers
     * @param  array<int, list<string>>  $rows
     */
    public static function render(array $headers, array $rows): string
    {
        $widths = array_map(
            fn (int $column): int => max([mb_strwidth($headers[$column]), ...array_map(fn (array $row): int => mb_strwidth($row[$column]), $rows)]),
            array_keys($headers),
        );

        return implode("\n", [
            self::row($headers, $widths),
            '|'.implode('|', array_map(fn (int $width): string => str_repeat('-', $width + 2), $widths)).'|',
            ...array_map(fn (array $row): string => self::row($row, $widths), $rows),
        ]);
    }

    /**
     * @param  list<string>  $cells
     * @param  list<int>  $widths
     */
    private static function row(array $cells, array $widths): string
    {
        return '| '.implode(' | ', array_map(fn (string $cell, int $width): string => $cell.str_repeat(' ', $width - mb_strwidth($cell)), $cells, $widths)).' |';
    }
}
