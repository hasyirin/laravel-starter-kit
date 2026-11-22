<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Filament\Support\Components\Attributes\Unexposed;

trait HidesBroadcastChannel
{
    #[Unexposed]
    public function getBroadcastChannel(): ?string
    {
        if ($this->broadcastingOff()) {
            return null;
        }

        return parent::getBroadcastChannel();
    }

    private function broadcastingOff(): bool
    {
        $connection = config('broadcasting.default');

        if (! is_string($connection) || $connection === 'null') {
            return true;
        }

        $driver = config("broadcasting.connections.{$connection}.driver");

        return ! is_string($driver) || in_array($driver, ['log', 'null'], true);
    }
}
