<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Settings;

use App\Domain\User\Models\User;
use App\Filament\Actions\CreateAction;
use App\Livewire\Concerns\ConfirmsPassword;
use App\Livewire\Concerns\RequiresVerification;
use App\Livewire\DataTables\PasskeyDataTable as DataTable;
use App\Livewire\Settings\PasskeyModal;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Passkey;

class PasskeyDataTable extends DataTable
{
    use ConfirmsPassword;
    use RequiresVerification;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->heading((string) __('user/passkeys.heading'))
            ->description((string) __('user/passkeys.description'))
            ->emptyStateHeading((string) __('user/passkeys.empty.heading'));
    }

    /**
     * @return array<int|string, string>
     */
    protected function getListeners(): array
    {
        return [...parent::getListeners(), PasskeyModal::event() => '$refresh'];
    }

    /**
     * @return HasMany<Passkey, Model>
     */
    protected function relationship(): HasMany
    {
        return $this->user()->passkeys()->latest();
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('user/passkeys.add.button'))
                ->action(fn () => $this->dispatch('show')->to(PasskeyModal::class)),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            Action::make('remove')
                ->label((string) __('user/passkeys.remove.submit'))
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading((string) __('user/passkeys.remove.heading'))
                ->modalDescription(fn (Passkey $passkey): string => (string) __('user/passkeys.remove.description', ['name' => $passkey->name]))
                ->modalSubmitActionLabel((string) __('user/passkeys.remove.submit'))
                ->action(function (Passkey $passkey): void {
                    if (! $this->passwordConfirmed()) {
                        return;
                    }

                    resolve(DeletePasskey::class)($this->user(), $passkey);
                }),
        ];
    }

    protected function paginated(): bool
    {
        return false;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
