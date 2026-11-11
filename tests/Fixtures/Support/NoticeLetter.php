<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Organization\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class NoticeLetter extends LetterType
{
    public function __construct(public ?string $extraKey = null, public ?string $drop = null) {}

    public function view(): string
    {
        return 'tests::letters.notice';
    }

    /**
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Field::text('organizationName'),
            Field::group('works', 'Works', [Field::text('contractor')->optional()]),
            Field::date('issuedOn'),
        ];
    }

    public function source(): string
    {
        return Organization::class;
    }

    public function data(Model $source, CarbonImmutable $date): array
    {
        /** @var Organization $source */
        $values = [
            'organizationName' => $source->name,
            'works' => null,
            'issuedOn' => $date->toDateString(),
        ];

        if ($this->extraKey !== null) {
            $values[$this->extraKey] = 'x';
        }

        if ($this->drop !== null) {
            unset($values[$this->drop]);
        }

        return $values;
    }
}
