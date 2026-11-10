<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;

final readonly class PrintDetails
{
    /**
     * @param  list<CarbonCopy>  $carbonCopies
     */
    public function __construct(
        public ?string $referenceNumber,
        public ?int $sequenceNumber,
        public ?CarbonImmutable $date,
        public ?User $signer,
        public array $carbonCopies,
        public bool $withLetterhead,
        public bool $computerSigned,
    ) {}

    /**
     * @return array{referenceNumber: string|null, sequenceNumber: int|null, date: string|null, signer: array{id: int, name: string}|null, carbonCopies: array<int, array{name: string, type: string|null, id: int|null}>, withLetterhead: bool, computerSigned: bool}
     */
    public function toArray(): array
    {
        return [
            'referenceNumber' => $this->referenceNumber,
            'sequenceNumber' => $this->sequenceNumber,
            'date' => $this->date?->toDateString(),
            'signer' => $this->signer instanceof User ? ['id' => $this->signer->id, 'name' => $this->signer->name] : null,
            'carbonCopies' => collect($this->carbonCopies)->map(fn (CarbonCopy $copy): array => $copy->toArray())->values()->all(),
            'withLetterhead' => $this->withLetterhead,
            'computerSigned' => $this->computerSigned,
        ];
    }
}
