<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;
use App\Livewire\Concerns\RequiresVerification;
use Livewire\Attributes\On;

class VerifiedPanel extends Component
{
    use RequiresVerification;

    public static int $runs = 0;

    public string $note = '';

    public function ping(): void
    {
        self::$runs++;
    }

    #[On('poke')]
    public function poke(): void
    {
        self::$runs++;
    }

    public function updating(): void
    {
        self::$runs++;
    }

    public function updatedNote(): void
    {
        self::$runs++;
    }

    public function render(): string
    {
        self::$runs++;

        return <<<'BLADE'
            <div>Protected content</div>
            BLADE;
    }
}
