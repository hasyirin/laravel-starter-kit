<?php

declare(strict_types=1);

namespace App\Filament\Tables\Columns;

use App\Domain\User\Support\Preferences;
use DateTimeInterface;
use Filament\Tables\Columns\TextColumn;
use Override;

class TimestampColumn extends TextColumn
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(fn (?DateTimeInterface $state): ?string => Preferences::current()->datetime($state))
            ->alignEnd();
    }
}
