<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Contracts;

interface HasColor extends \Filament\Support\Contracts\HasColor
{
    /**
     * @return string|array<string>
     */
    public function color(): array|string;
}
