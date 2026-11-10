<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Enums\NumberingMode;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\ReferenceSequence;
use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AssignReferenceNumber
{
    use Actionable;

    public function execute(Letter $letter, User $printer): Letter
    {
        $locked = $letter->getConnection()->transaction(function () use ($letter, $printer): Letter {
            $locked = Letter::query()->lockForUpdate()->findOrFail($letter->id);

            if ($locked->reference_number !== null) {
                return $locked;
            }

            $type = $locked->letter_type;

            throw_if($type->numbering() === NumberingMode::Manual, ValidationException::withMessages(['reference_number' => __('letters/letters.reference_number_required')]));

            $locked->date ??= CarbonImmutable::today(config()->string('system.timezone'));
            $locked->sequence_key ??= ReferenceSequence::key($type->sequenceKey($locked, $printer));

            $next = ReferenceSequence::for($type, $locked->sequence_key)->next();

            $locked->sequence_number = $next['number'];
            $locked->sequence_cycle = $next['cycle'];
            $locked->reference_number = $type->reference($next['number'], $locked);
            $locked->save();

            return $locked;
        });

        $letter->setRawAttributes($locked->getAttributes(), sync: true);

        return $letter;
    }
}
