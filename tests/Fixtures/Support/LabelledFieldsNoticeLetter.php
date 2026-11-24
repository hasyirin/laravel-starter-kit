<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Organization\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class LabelledFieldsNoticeLetter extends NoticeLetter
{
    /**
     * @return list<Field>
     */
    public function fields(): array
    {
        return [
            Field::text('organizationName')->label('Organisation name'),
            Field::group('works', 'Works', [Field::text('contractor')->label('Contractor name')->optional()]),
            Field::date('issuedOn'),
        ];
    }

    /**
     * @return list<Field>
     */
    public function extraFields(): array
    {
        return [
            Field::text('remark')->label('Remark for the office'),
            Field::table('lines', [Field::number('amount')->label('Unit price')]),
            Field::group('guarantor', 'Guarantor details', [Field::text('name')->label('Full name'), Field::text('phone')]),
        ];
    }

    public function data(Model $source, CarbonImmutable $date): array
    {
        /** @var Organization $source */
        return [
            'organizationName' => $source->name,
            'works' => ['contractor' => $source->name],
            'issuedOn' => $date->toDateString(),
        ];
    }
}
