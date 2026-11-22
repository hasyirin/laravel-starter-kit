<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

class DateProperties extends Component
{
    public ?CarbonImmutable $startsOn = null;

    public ?CarbonImmutable $endsOn = null;

    public ?DateTimeImmutable $heldOn = null;

    public function mount(): void
    {
        $this->endsOn = CarbonImmutable::parse('2026-12-31');
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>{{ $startsOn?->toDateString() }} {{ $endsOn?->toDateString() }} {{ $heldOn?->format('Y-m-d') }}</div>
            BLADE;
    }
}
