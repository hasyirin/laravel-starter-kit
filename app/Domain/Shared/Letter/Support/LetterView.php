<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use App\Domain\Shared\Letter\Models\Letterhead;
use Carbon\CarbonImmutable;

final readonly class LetterView
{
    /**
     * @param  list<CarbonCopy>  $carbonCopies
     */
    public function __construct(
        public ?string $referenceNumber,
        public CarbonImmutable $date,
        public string $formattedDate,
        public ?string $signerName,
        public ?string $signerPosition,
        public array $carbonCopies,
        public Letterhead|LetterheadView|null $letterhead,
        public bool $computerSigned,
    ) {}
}
