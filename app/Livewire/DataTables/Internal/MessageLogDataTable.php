<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal;

use App\Domain\Shared\Message\Models\Message;
use App\Livewire\DataTables\MessageLogDataTable as DataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class MessageLogDataTable extends DataTable
{
    #[Locked]
    public Model $resource;

    public function mount(): void
    {
        $this->authorize('viewAny', Message::class);
    }

    /**
     * @return Builder<Message>
     */
    protected function query(): Builder
    {
        return $this->messages()->whereMorphedTo('resource', $this->resource);
    }
}
