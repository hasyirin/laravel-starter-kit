<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\System;

use App\Domain\Shared\Api\Actions\DeleteApiClient;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;

#[Defer]
class ApiClientDataTable extends DataTable
{
    public function mount(): void
    {
        $this->authorize('viewAny', ApiClient::class);
    }

    /**
     * @return Builder<ApiClient>
     */
    protected function query(): Builder
    {
        return ApiClient::query()
            ->withCount('activeTokens')
            ->withMax('tokens', 'last_used_at')
            ->withCasts(['tokens_max_last_used_at' => 'datetime']);
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('api/api-clients.api_client'))
                ->visible(fn (): bool => $this->can('create', ApiClient::class))
                ->action($this->to(...)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')
                ->label((string) __('common/inputs.name'))
                ->searchable()
                ->sortable(),
            IconColumn::make('enabled')
                ->label((string) __('api/api-clients.enabled'))
                ->boolean(),
            TextColumn::make('active_tokens_count')
                ->label((string) __('api/api-clients.active_tokens_count'))
                ->numeric()
                ->sortable(),
            IconColumn::make('allows_any_ip_address')
                ->label((string) __('api/api-clients.any_ip_address'))
                ->boolean()
                ->trueIcon(Heroicon::ExclamationTriangle)
                ->trueColor('warning'),
            TimestampColumn::make('tokens_max_last_used_at')
                ->label((string) __('api/api-clients.last_used_at'))
                ->sortable(),
            TextColumn::make('service')
                ->label((string) __('api/api-clients.service')),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn (ApiClient $apiClient): bool => $this->can('view', $apiClient))
                ->action($this->to(...)),

            DeleteAction::make()
                ->through(DeleteApiClient::class)
                ->visible(fn (ApiClient $apiClient): bool => $this->can('delete', $apiClient)),
        ];
    }

    protected function to(?ApiClient $record = null): void
    {
        if (! $record instanceof ApiClient) {
            $this->redirect(route('system.api-clients.create'), navigate: true);

            return;
        }

        $this->redirect(route('system.api-clients.show', $record), navigate: true);
    }
}
