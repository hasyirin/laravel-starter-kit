<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Reactive;

trait CanBeDisabled
{
    #[Reactive]
    public bool $disabled = false;

    public function isDisabled(?string $part = null): bool
    {
        return $this->disabled;
    }

    public function renderingCanBeDisabled(View $view): void
    {
        $view->with('disabled', $this->isDisabled());
    }

    protected function ensureEnabled(?string $part = null): void
    {
        abort_if($this->isDisabled($part), 403);
    }
}
