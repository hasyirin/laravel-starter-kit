<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Internal;

use App\Domain\User\Actions\DeleteOtherUser;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use App\Livewire\Pages\Internal\Controls\User\ImpersonateModal;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;

#[Defer]
class UserDataTable extends DataTable
{
    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    /**
     * @return Builder<User>
     */
    protected function query(): Builder
    {
        return User::query()->with('profile.roles');
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => $this->can('create', User::class))
                ->action($this->to(...)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label((string) __('common/inputs.name'))->searchable()->sortable(),
            TextColumn::make('email')->label((string) __('common/inputs.email'))->searchable()->sortable(),
            TextColumn::make('roles')
                ->label((string) __('user/users.roles'))
                ->badge()
                ->getStateUsing(fn (User $user): array => $user->profile?->roles->pluck('label')->all() ?? []),
            TimestampColumn::make('created_at'),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn (User $user): bool => $this->can('view', $user))
                ->action($this->to(...)),

            Action::make('impersonate')
                ->label((string) __('user/users.impersonation.action'))
                ->icon(Heroicon::UserCircle)
                ->visible(fn (User $user): bool => $this->impersonatable($user))
                ->action(fn (User $user) => $this->dispatch('show', id: $user->id)->to(ImpersonateModal::class)),

            DeleteAction::make()
                ->through(DeleteOtherUser::class)
                ->visible(fn (User $user): bool => $this->can('delete', $user) && ! $user->is(auth()->user())),
        ];
    }

    private function impersonatable(User $user): bool
    {
        /** @var User $actor */
        $actor = auth()->user();

        return Impersonation::allows($actor, $user);
    }

    protected function to(?User $record = null): void
    {
        if (! $record instanceof User) {
            $this->redirect(route('internal.controls.users.create'), navigate: true);

            return;
        }

        $this->redirect(route('internal.controls.users.show', $record), navigate: true);
    }
}
