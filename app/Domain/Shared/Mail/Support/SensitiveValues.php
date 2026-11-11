<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Support;

use SensitiveParameter;

class SensitiveValues
{
    /**
     * @param  list<string>  $values
     */
    public function __construct(#[SensitiveParameter] private readonly array $values) {}

    public function mask(mixed $text): ?string
    {
        if (! is_string($text)) {
            return null;
        }

        return str_replace($this->forms(), '[hidden]', $text);
    }

    public function appearIn(string $text): bool
    {
        return collect($this->forms())->contains(fn (string $form): bool => str_contains($text, $form));
    }

    /**
     * @return array<int, string>
     */
    private function forms(): array
    {
        return collect($this->values)
            ->filter()
            ->flatMap(fn (string $value): array => [$value, e($value), htmlspecialchars($value, ENT_QUOTES | ENT_HTML5), rawurlencode($value), urlencode($value)])
            ->unique()
            ->sortByDesc(fn (string $form): int => strlen($form))
            ->values()
            ->all();
    }
}
