<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal\User;

use App\Domain\User\Models\User;
use App\Livewire\DataTables\SessionDataTable as DataTable;
use Filament\Actions\Action;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Livewire\Livewire;

#[Defer]
class SessionDataTable extends DataTable
{
    #[Locked]
    public User $user;

    public function mount(): void
    {
        $this->authorizeEnd();
    }

    protected function user(): User
    {
        return $this->user;
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [$this->endAllAction()->before($this->authorizeEnd(...))];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [$this->endAction()->before($this->authorizeEnd(...))];
    }

    protected function endAllKey(): string
    {
        if ($this->user->is(auth()->user())) {
            return parent::endAllKey();
        }

        return 'user/sessions.end_all_of_user';
    }

    protected function passwordReturnUrl(): string
    {
        return url()->query(Livewire::originalUrl(), ['tab' => 'sessions']);
    }

    private function authorizeEnd(): void
    {
        $this->authorize('update', [$this->user, 'sessions']);
    }
}
