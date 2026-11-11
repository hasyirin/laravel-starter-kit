<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

final readonly class LetterheadView
{
    /**
     * @param  view-string  $view
     */
    public function __construct(
        public string $view,
        public int $heightMm,
        public bool $everyPage = false,
    ) {}
}
