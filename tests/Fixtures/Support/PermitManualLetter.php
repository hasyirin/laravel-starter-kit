<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\Field;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Models\Permit;

class PermitManualLetter extends PermitExtraLetter
{
    public function view(): string
    {
        return 'tests::letters.manual';
    }

    /**
     * @return list<Field>
     */
    public function fields(): array
    {
        return [Field::text('title'), Field::group('works', 'Works', [Field::text('contractor')])];
    }

    public function data(Model $source, CarbonImmutable $date): array
    {
        /** @var Permit $source */
        return ['title' => $source->name, 'works' => null];
    }
}
