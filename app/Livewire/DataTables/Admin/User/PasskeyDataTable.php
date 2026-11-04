<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Admin\User;

use App\Domain\User\Actions\RevokeUserPasskey;
use App\Domain\User\Models\User;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Passkeys\Passkey;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class PasskeyDataTable extends DataTable
{
    #[Locked]
    public User $user;

    public function mount(): void
    {
        $this->authorize('view', $this->user);
    }

    /**
     * @return HasMany<Passkey, Model>
     */
    protected function relationship(): HasMany
    {
        return $this->user->passkeys()->latest();
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label((string) __('user/users.passkeys_tab.name')),
            TextColumn::make('authenticator')->label((string) __('user/users.passkeys_tab.authenticator')),
            TimestampColumn::make('created_at')->label((string) __('user/users.passkeys_tab.created_at')),
            TimestampColumn::make('last_used_at')->label((string) __('user/users.passkeys_tab.last_used_at')),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            Action::make('revoke')
                ->label((string) __('user/users.passkeys_tab.revoke'))
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading((string) __('user/users.passkeys_tab.revoke_heading'))
                ->modalSubmitActionLabel((string) __('user/users.passkeys_tab.revoke'))
                ->visible(fn (): bool => $this->canRevoke())
                ->action(function (Passkey $passkey, RevokeUserPasskey $revokeUserPasskey): void {
                    $this->authorize('update', $this->user);

                    $revokeUserPasskey->execute($this->actor(), $this->user, $passkey->id);

                    Flux::toast(variant: 'success', text: __('user/users.passkeys_tab.revoked'));
                }),
        ];
    }

    private function actor(): User
    {
        /** @var User $actor */
        $actor = auth()->user();

        return $actor;
    }

    private function canRevoke(): bool
    {
        return $this->can('update', $this->user) && ! $this->actor()->is($this->user);
    }
}
