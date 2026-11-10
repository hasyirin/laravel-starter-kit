<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Support\CarbonCopy;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterView;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Placement;
use Carbon\CarbonImmutable;
use DateTimeZone;
use IntlDateFormatter;

class BuildLetterView
{
    use Actionable;

    /**
     * @param  list<CarbonCopy>  $carbonCopies
     */
    public function execute(
        LetterType $type,
        ?string $referenceNumber,
        CarbonImmutable $date,
        ?Profile $signerProfile,
        array $carbonCopies,
        bool $withLetterhead,
        bool $computerSigned,
        User $printer,
    ): LetterView {
        $letterhead = $withLetterhead
            ? ResolveLetterhead::make()->execute(
                $type,
                $signerProfile instanceof Profile ? Placement::of($signerProfile) : null,
                Placement::of($printer->profile),
            )
            : null;

        return new LetterView(
            $referenceNumber,
            $date,
            $this->formatDate($type, $date),
            $signerProfile?->name,
            $signerProfile?->position?->name,
            $carbonCopies,
            $letterhead,
            $computerSigned,
        );
    }

    private function formatDate(LetterType $type, CarbonImmutable $date): string
    {
        return (string) new IntlDateFormatter(
            $type->locale(),
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            new DateTimeZone($date->format('P')),
            IntlDateFormatter::GREGORIAN,
            $type->dateFormat(),
        )->format($date);
    }
}
