<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\LetterType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Models\Permit;

class PermitLetter extends LetterType
{
    public function view(): string
    {
        return 'tests::letters.plain';
    }

    public function fields(): array
    {
        return [];
    }

    public function source(): string
    {
        return Permit::class;
    }

    public function data(Model $source, CarbonImmutable $date): array
    {
        return [];
    }
}
