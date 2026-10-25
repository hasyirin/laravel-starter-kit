<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Sections;

use App\Domain\Shared\Documentation\Contracts\Section;
use InvalidArgumentException;
use PhpToken;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class TestRules implements Section
{
    public function render(array $options): string
    {
        $folder = $options['path'] ?? throw new InvalidArgumentException('The [test-rules] section needs a path option.');
        $path = str_starts_with($folder, '/') ? $folder : base_path($folder);

        throw_unless(is_dir($path), InvalidArgumentException::class, "The test folder [{$folder}] does not exist.");

        return collect(iterator_to_array(Finder::create()->files()->in($path)->name('*Test.php')->sortByName()))
            ->map(fn (SplFileInfo $file): array => ['subject' => $file->getBasename('Test.php'), 'names' => $this->names($file->getContents())])
            ->filter(fn (array $test): bool => $test['names'] !== [])
            ->map(fn (array $test): string => "### {$test['subject']}\n\n".collect($test['names'])->map(fn (string $name): string => "- {$name}")->implode("\n"))
            ->implode("\n\n");
    }

    /**
     * @return list<string>
     */
    private function names(string $code): array
    {
        $tokens = array_values(array_filter(PhpToken::tokenize($code), fn (PhpToken $token): bool => ! $token->isIgnorable()));
        $names = [];

        foreach ($tokens as $index => $token) {
            $open = $tokens[$index + 1] ?? null;
            $literal = $tokens[$index + 2] ?? null;

            if (! $token->is(T_STRING) || ! in_array($token->text, ['test', 'it'], true) || $open?->text !== '(' || $literal === null || ! $literal->is(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }

            if (($tokens[$index - 1] ?? null)?->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW]) || $this->isPending($tokens, $index + 1)) {
                continue;
            }

            $name = $this->string($literal->text);
            $names[] = $token->text === 'it' ? "it {$name}" : $name;
        }

        return array_values(array_unique($names));
    }

    /**
     * @param  list<PhpToken>  $tokens
     */
    private function isPending(array $tokens, int $open): bool
    {
        $depth = 0;

        foreach (array_slice($tokens, $open, preserve_keys: true) as $index => $token) {
            if ($token->is(['(', '[', '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $depth++;
            } elseif ($token->is([')', ']', '}'])) {
                $depth--;
            } elseif ($depth === 0 && $token->text === ';') {
                return false;
            } elseif ($depth === 0 && in_array($token->text, ['todo', 'skip'], true) && $tokens[$index - 1]->is(T_OBJECT_OPERATOR)) {
                return true;
            }
        }

        return false;
    }

    private function string(string $literal): string
    {
        $value = substr($literal, 1, -1);

        return str_starts_with($literal, "'") ? str_replace(["\\'", '\\\\'], ["'", '\\'], $value) : stripcslashes($value);
    }
}
