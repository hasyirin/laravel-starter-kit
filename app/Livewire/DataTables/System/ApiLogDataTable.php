<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\System;

use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Common\Support\AuditLog;
use App\Domain\User\Support\Preferences;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use App\Livewire\Pages\System\ApiCallModal;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;

#[Defer]
class ApiLogDataTable extends DataTable
{
    private const array STATUS_CLASSES = ['2xx', '3xx', '4xx', '5xx', 'none'];

    #[Locked]
    public ?ApiClient $apiClient = null;

    public function mount(): void
    {
        $this->authorize('viewAny', ApiCall::class);
    }

    /**
     * @return Builder<ApiCall>
     */
    protected function query(): Builder
    {
        return ApiCall::query()
            ->when($this->apiClient, fn (Builder $query, ApiClient $apiClient): Builder => $query->whereBelongsTo($apiClient))
            ->latest('id');
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TimestampColumn::make('created_at')->label((string) __('api/api-calls.time'))->alignStart(),
            TextColumn::make('direction')->label((string) __('api/api-calls.direction'))->badge()->color('gray'),
            TextColumn::make('party')
                ->label((string) __('api/api-calls.party'))
                ->state(fn (ApiCall $apiCall): ?string => $apiCall->direction === ApiCallDirection::Inbound ? $apiCall->api_client_name : $apiCall->service)
                ->description(fn (ApiCall $apiCall): ?string => $apiCall->direction === ApiCallDirection::Outbound ? $apiCall->api_client_name : null),
            TextColumn::make('method')->label((string) __('api/api-calls.method')),
            TextColumn::make('url')
                ->label((string) __('api/api-calls.url'))
                ->limit(60)
                ->wrap()
                ->searchable(),
            TextColumn::make('status')->label((string) __('api/api-calls.status')),
            TextColumn::make('outcome')->label((string) __('api/api-calls.outcome'))->badge(),
            TextColumn::make('duration_ms')
                ->label((string) __('api/api-calls.duration'))
                ->formatStateUsing(fn (int $state): string => (string) __('api/api-calls.duration_ms', ['duration' => $state]))
                ->alignEnd(),
        ];
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        return [
            SelectFilter::make('direction')
                ->label((string) __('api/api-calls.direction'))
                ->options(ApiCallDirection::class)
                ->multiple(),
            SelectFilter::make('outcome')
                ->label((string) __('api/api-calls.outcome'))
                ->options(ApiCallOutcome::class)
                ->multiple(),
            SelectFilter::make('api_client')
                ->label((string) __('api/api-calls.api_client'))
                ->relationship('apiClient', 'name')
                ->searchable()
                ->multiple()
                ->visible(! $this->apiClient instanceof ApiClient),
            SelectFilter::make('service')
                ->label((string) __('api/api-calls.service'))
                ->options(fn (): array => $this->services())
                ->multiple(),
            SelectFilter::make('status')
                ->label((string) __('api/api-calls.status'))
                ->options(fn (): array => (array) __('api/api-calls.status_classes'))
                ->multiple()
                ->query(fn (Builder $query, array $data): Builder => $this->withStatusClasses($query, $data['values'] ?? [])),
            Filter::make('created_at')
                ->label((string) __('api/api-calls.time'))
                ->schema([
                    DatePicker::make('from')->label((string) __('api/api-calls.from')),
                    DatePicker::make('until')->label((string) __('api/api-calls.until')),
                ])
                ->query(fn (Builder $query, array $data) => AuditLog::between($query, $data))
                ->indicateUsing(fn (array $data): array => $this->timeIndicators($data)),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()->action(fn (ApiCall $apiCall) => $this->dispatch('show', id: $apiCall->id)->to(ApiCallModal::class)),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function services(): array
    {
        return ApiCall::query()
            ->whereNotNull('service')
            ->distinct()
            ->orderBy('service')
            ->get(['service'])
            ->mapWithKeys(fn (ApiCall $apiCall): array => [(string) $apiCall->service => (string) $apiCall->service])
            ->all();
    }

    /**
     * @param  Builder<ApiCall>  $query
     * @return Builder<ApiCall>
     */
    private function withStatusClasses(Builder $query, mixed $classes): Builder
    {
        $selected = collect(self::STATUS_CLASSES)->filter(fn (string $class): bool => is_array($classes) && in_array($class, $classes, true));

        if ($selected->isEmpty()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($selected): void {
            $selected->each(function (string $class) use ($query): void {
                if ($class === 'none') {
                    $query->orWhereNull('status');

                    return;
                }

                $lowest = (int) $class[0] * 100;
                $query->orWhereBetween('status', [$lowest, $lowest + 99]);
            });
        });
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<string, string>
     */
    private function timeIndicators(array $data): array
    {
        return collect(['from', 'until'])
            ->filter(fn (string $field): bool => is_string($data[$field] ?? null) && $data[$field] !== '')
            ->mapWithKeys(fn (string $field): array => [$field => __("api/api-calls.$field").': '.Preferences::current()->calendarDate(CarbonImmutable::parse((string) $data[$field]))])
            ->all();
    }
}
