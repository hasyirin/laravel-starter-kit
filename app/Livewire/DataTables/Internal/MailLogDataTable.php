<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal;

use App\Domain\Shared\Mail\Models\Mail;
use App\Livewire\DataTables\MailLogDataTable as DataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class MailLogDataTable extends DataTable
{
    #[Locked]
    public Model $resource;

    public function mount(): void
    {
        $this->authorize('viewAny', Mail::class);
    }

    /**
     * @return Builder<Mail>
     */
    protected function query(): Builder
    {
        return Mail::query()->whereMorphedTo('resource', $this->resource)->latest('id');
    }
}
