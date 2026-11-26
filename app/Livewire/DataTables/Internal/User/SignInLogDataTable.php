<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\User;

use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\SignInLogDataTable as DataTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class SignInLogDataTable extends DataTable
{
    #[Locked]
    public User $user;

    public function mount(): void
    {
        $this->authorize('view', [$this->user, 'sign-in-log']);
    }

    /**
     * @return Builder<SignInEntry>
     */
    protected function query(): Builder
    {
        return parent::query()->where(fn (Builder $query): Builder => $query->whereBelongsTo($this->user)->orWhereBelongsTo($this->user, 'impersonator'));
    }
}
