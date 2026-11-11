<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\System;

use App\Domain\Shared\Message\Models\Message;
use App\Livewire\DataTables\MessageLogDataTable as DataTable;
use Livewire\Attributes\Defer;

#[Defer]
class MessageLogDataTable extends DataTable
{
    public function mount(): void
    {
        $this->authorize('viewAny', Message::class);
    }
}
