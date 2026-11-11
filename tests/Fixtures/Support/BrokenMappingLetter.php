<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class BrokenMappingLetter extends NoticeLetter
{
    public function data(Model $source, CarbonImmutable $date): array
    {
        throw new InvalidArgumentException('Broken mapping.');
    }
}
