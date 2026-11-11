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
use LogicException;

class AssignReferenceNumber
{
    use Actionable;

    public function execute(Letter $letter, User $printer, ?CarbonImmutable $date = null): Letter
    {
        $locked = $letter->getConnection()->transaction(function () use ($letter, $printer, $date): Letter {
            $locked = Letter::query()->lockForUpdate()->findOrFail($letter->id);

            $locked->date ??= $date ?? CarbonImmutable::today(config()->string('system.timezone'));

            if ($locked->reference_number !== null) {
                $locked->save();

                return $locked;
            }

            $type = $locked->letter_type;

            throw_if($type->numbering() === NumberingMode::Manual, ValidationException::withMessages(['reference_number' => __('letters/letters.reference_number_required')]));

            $locked->sequence_key ??= ReferenceSequence::key($type->sequenceKey($locked, $printer));

            $next = ReferenceSequence::for($type, $locked->sequence_key)->next();
            $reference = $type->reference($next['number'], $locked);

            $taken = Letter::withTrashed()
                ->where('sequence_key', $locked->sequence_key)
                ->where('reference_number', $reference)
                ->exists();

            throw_if($taken, LogicException::class, "The letter type [{$locked->type}] gave the reference number [{$reference}] again. Its reference() must include the reset period of its sequence.");

            $locked->sequence_number = $next['number'];
            $locked->sequence_cycle = $next['cycle'];
            $locked->reference_number = $reference;
            $locked->save();

            return $locked;
        });

        $letter->setRawAttributes($locked->getAttributes(), sync: true);

        return $letter;
    }
}
