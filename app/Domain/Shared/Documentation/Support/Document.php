<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Support;

use InvalidArgumentException;
use RuntimeException;

class Document
{
    private const string CODE_BLOCK = '/^ *```.*?^ *```$/ms';

    private const string CODE_BLOCK_OR_SECTION = '/(^ *```.*?^ *```$)|<!-- generated:([a-z0-9-]+)((?: [a-z0-9-]+="[^"]*")*) -->\n.*?<!-- \/generated:\2 -->/ms';

    public function __construct(private readonly string $contents) {}

    /**
     * @param  callable(string, array<string, string>): string  $render
     */
    public function render(callable $render): string
    {
        preg_match_all(self::CODE_BLOCK_OR_SECTION, $this->contents, $matches);
        $sections = count(array_filter($matches[1], fn (string $codeBlock): bool => $codeBlock === ''));
        $markers = preg_match_all('/^<!-- generated:/m', preg_replace(self::CODE_BLOCK, '', $this->contents) ?? '');

        throw_if($markers !== $sections, InvalidArgumentException::class, 'A generated section has no matching closing marker.');

        return preg_replace_callback(self::CODE_BLOCK_OR_SECTION, function (array $match) use ($render): string {
            if ($match[1] !== '') {
                return $match[0];
            }

            preg_match_all('/([a-z0-9-]+)="([^"]*)"/', $match[3], $options);
            $content = trim($render($match[2], array_combine($options[1], $options[2])));

            return "<!-- generated:{$match[2]}{$match[3]} -->\n\n{$content}\n\n<!-- /generated:{$match[2]} -->";
        }, $this->contents) ?? throw new RuntimeException(preg_last_error_msg());
    }

    /**
     * @return list<array{line: int, reference: string}>
     */
    public function references(): array
    {
        $references = [];
        $generated = false;
        $fenced = false;

        foreach (explode("\n", $this->contents) as $index => $line) {
            if (str_starts_with(ltrim($line), '```')) {
                $fenced = ! $fenced;
            } elseif ($fenced) {
                continue;
            } elseif (str_starts_with($line, '<!-- generated:')) {
                $generated = true;
            } elseif (str_starts_with($line, '<!-- /generated:')) {
                $generated = false;
            } elseif (! $generated) {
                preg_match_all('/`([^`]+)`/', $line, $matches);

                foreach ($matches[1] as $reference) {
                    $references[] = ['line' => $index + 1, 'reference' => $reference];
                }
            }
        }

        return $references;
    }
}
