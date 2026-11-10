<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Models\Crest;

class CrestLetter extends LetterType
{
    public function view(): string
    {
        return 'tests::letters.plain';
    }

    /**
     * @return list<Field>
     */
    public function fields(): array
    {
        return [Field::image('logo')];
    }

    public function source(): string
    {
        return Crest::class;
    }

    public function data(Model $source, CarbonImmutable $date): array
    {
        return ['logo' => (string) config('test.crest_logo')];
    }
}
