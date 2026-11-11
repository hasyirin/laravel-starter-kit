<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\System;

use App\Domain\Shared\Mail\Models\Mail;
use App\Livewire\DataTables\MailLogDataTable as DataTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;

#[Defer]
class MailLogDataTable extends DataTable
{
    public function mount(): void
    {
        $this->authorize('viewAny', Mail::class);
    }

    /**
     * @return Builder<Mail>
     */
    protected function query(): Builder
    {
        return Mail::query()->latest('id');
    }
}
