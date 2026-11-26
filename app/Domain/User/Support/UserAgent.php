<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

final readonly class UserAgent
{
    /**
     * The first match wins, because many browsers also name Chrome or Safari, and many platforms also name Linux or Mac OS X.
     *
     * @var array<string, list<string>>
     */
    private const array BROWSERS = [
        'Edge' => ['Edg/', 'EdgA/', 'EdgiOS/', 'Edge/'],
        'Opera' => ['OPR/', 'OPT/', 'Opera'],
        'Samsung Internet' => ['SamsungBrowser/'],
        'Chrome' => ['Chrome/', 'CriOS/'],
        'Firefox' => ['Firefox/', 'FxiOS/'],
        'Safari' => ['Safari/'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const array PLATFORMS = [
        'Windows' => ['Windows'],
        'iOS' => ['iPhone', 'iPad', 'iPod'],
        'Android' => ['Android'],
        'ChromeOS' => ['CrOS'],
        'macOS' => ['Macintosh', 'Mac OS X'],
        'Linux' => ['Linux'],
    ];

    public function __construct(private ?string $value) {}

    public function browser(): string
    {
        return $this->match(self::BROWSERS);
    }

    public function platform(): string
    {
        return $this->match(self::PLATFORMS);
    }

    /**
     * @param  array<string, list<string>>  $names
     */
    private function match(array $names): string
    {
        $name = collect($names)->search(fn (array $needles): bool => str($this->value ?? '')->contains($needles));

        if (! is_string($name)) {
            return (string) __('user/sessions.unknown');
        }

        return $name;
    }
}
