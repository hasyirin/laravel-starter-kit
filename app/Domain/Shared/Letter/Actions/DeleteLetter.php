<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\Shared\Letter\Support\ReferenceSequence;

class DeleteLetter
{
    use Actionable;

    public function execute(Letter $letter): void
    {
        $letter->getConnection()->transaction(function () use ($letter): void {
            $letter->delete();

            $type = LetterTypes::tryFind($letter->type);

            if (! $type instanceof LetterType || $letter->sequence_key === null || $letter->sequence_number === null || $letter->sequence_cycle === null) {
                return;
            }

            ReferenceSequence::for($type, $letter->sequence_key)->release($letter->sequence_number, $letter->sequence_cycle);
        });
    }
}
