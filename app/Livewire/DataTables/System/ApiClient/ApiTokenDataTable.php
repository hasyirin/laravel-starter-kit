<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\System\ApiClient;

use App\Domain\Shared\Api\Actions\RevokeApiToken;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Filament\Actions\CreateAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use App\Livewire\Pages\System\ApiClient\ApiTokenModal;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class ApiTokenDataTable extends DataTable
{
    private const int EXPIRES_SOON_DAYS = 30;

    #[Locked]
    public ApiClient $apiClient;

    public function mount(): void
    {
        $this->authorize('view', $this->apiClient);
    }

    /**
     * @return array<int|string, string>
     */
    protected function getListeners(): array
    {
        return [...parent::getListeners(), ApiTokenModal::event() => '$refresh'];
    }

    /**
     * @return Builder<ApiToken>
     */
    protected function query(): Builder
    {
        return ApiToken::query()->whereBelongsTo($this->apiClient)->latest('id');
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('api/api-tokens.api_token'))
                ->visible(fn (): bool => $this->can('update', $this->apiClient))
                ->action(fn () => $this->dispatch('show')->to(ApiTokenModal::class)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('last_four')
                ->label((string) __('api/api-tokens.last_four'))
                ->fontFamily('mono'),
            TextColumn::make('scopes')
                ->label((string) __('api/api-tokens.scopes'))
                ->badge()
                ->placeholder((string) __('api/api-tokens.no_scopes')),
            TextColumn::make('state')
                ->label((string) __('api/api-tokens.state'))
                ->badge()
                ->state(fn (ApiToken $apiToken): string => (string) __('api/api-tokens.states.'.$this->state($apiToken)))
                ->color(fn (ApiToken $apiToken): string => match ($this->state($apiToken)) {
                    'active' => 'success',
                    'expires_soon' => 'warning',
                    default => 'gray',
                }),
            TimestampColumn::make('created_at')->label((string) __('api/api-tokens.created_at')),
            TimestampColumn::make('expires_at')
                ->label((string) __('api/api-tokens.expires_at'))
                ->placeholder((string) __('api/api-tokens.never')),
            TimestampColumn::make('last_used_at')->label((string) __('api/api-tokens.last_used_at')),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            Action::make('rotate')
                ->label((string) __('api/api-tokens.rotate'))
                ->icon(Heroicon::ArrowPath)
                ->visible(fn (ApiToken $apiToken): bool => $this->changeable($apiToken) && ! $this->full())
                ->action(fn (ApiToken $apiToken) => $this->dispatch('show', id: $apiToken->id)->to(ApiTokenModal::class)),

            Action::make('revoke')
                ->label((string) __('api/api-tokens.revoke'))
                ->icon(Heroicon::NoSymbol)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading((string) __('api/api-tokens.revoke_heading'))
                ->modalDescription((string) __('api/api-tokens.revoke_description'))
                ->modalSubmitActionLabel((string) __('api/api-tokens.revoke'))
                ->visible(fn (ApiToken $apiToken): bool => $this->changeable($apiToken))
                ->action(function (ApiToken $apiToken): void {
                    $this->authorize('update', $this->apiClient);

                    RevokeApiToken::make()->execute($apiToken);

                    Flux::toast(variant: 'success', text: __('api/api-tokens.revoked'));
                }),
        ];
    }

    private function state(ApiToken $apiToken): string
    {
        if ($apiToken->revoked) {
            return 'revoked';
        }

        if ($apiToken->expired) {
            return 'expired';
        }

        if ($apiToken->expires_at?->lte(now()->addDays(self::EXPIRES_SOON_DAYS)) === true) {
            return 'expires_soon';
        }

        return 'active';
    }

    private function changeable(ApiToken $apiToken): bool
    {
        return $this->can('update', $this->apiClient) && ! $apiToken->revoked && ! $apiToken->expired;
    }

    private function full(): bool
    {
        return $this->apiClient->activeTokens()->count() >= config()->integer('system.api.max_active_tokens');
    }
}
