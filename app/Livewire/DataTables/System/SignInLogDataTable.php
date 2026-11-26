<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\System;

use App\Domain\Shared\Common\Support\AuditLog;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Support\Preferences;
use App\Livewire\DataTables\SignInLogDataTable as DataTable;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Defer;

#[Defer]
class SignInLogDataTable extends DataTable
{
    public function mount(): void
    {
        $this->authorize('viewAny', SignInEntry::class);
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        return [
            SelectFilter::make('user')
                ->label((string) __('user/sign-in-log.user'))
                ->relationship('user', 'name')
                ->searchable()
                ->multiple()
                ->query(fn (Builder $query, array $data): Builder => $this->usedByOrImpersonatedBy($query, $data['values'] ?? [])),
            SelectFilter::make('result')
                ->label((string) __('user/sign-in-log.result'))
                ->options(SignInResult::class)
                ->multiple(),
            Filter::make('ip_address')
                ->label((string) __('user/sign-in-log.ip_address'))
                ->schema([
                    TextInput::make('ip_address')->label((string) __('user/sign-in-log.ip_address')),
                ])
                ->query(fn (Builder $query, array $data): Builder => $this->fromIpAddress($query, $data['ip_address'] ?? null))
                ->indicateUsing(fn (array $data): ?string => $this->ipAddressIndicator($data['ip_address'] ?? null)),
            Filter::make('created_at')
                ->label((string) __('user/sign-in-log.time'))
                ->schema([
                    DatePicker::make('from')->label((string) __('user/sign-in-log.from')),
                    DatePicker::make('until')->label((string) __('user/sign-in-log.until')),
                ])
                ->query(fn (Builder $query, array $data) => AuditLog::between($query, $data))
                ->indicateUsing(fn (array $data): array => $this->timeIndicators($data)),
        ];
    }

    /**
     * @param  Builder<SignInEntry>  $query
     * @return Builder<SignInEntry>
     */
    private function usedByOrImpersonatedBy(Builder $query, mixed $ids): Builder
    {
        if (! is_array($ids) || blank($ids)) {
            return $query;
        }

        return $query->where(fn (Builder $query): Builder => $query->whereIn('user_id', $ids)->orWhereIn('impersonator_id', $ids));
    }

    /**
     * @param  Builder<SignInEntry>  $query
     * @return Builder<SignInEntry>
     */
    private function fromIpAddress(Builder $query, mixed $ipAddress): Builder
    {
        if (! is_string($ipAddress) || blank($ipAddress)) {
            return $query;
        }

        return $query->where('ip_address', trim($ipAddress));
    }

    private function ipAddressIndicator(mixed $ipAddress): ?string
    {
        if (! is_string($ipAddress) || blank($ipAddress)) {
            return null;
        }

        return __('user/sign-in-log.ip_address').': '.trim($ipAddress);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<string, string>
     */
    private function timeIndicators(array $data): array
    {
        return collect(['from', 'until'])
            ->mapWithKeys(fn (string $field): array => [$field => AuditLog::date($data[$field] ?? null)])
            ->filter()
            ->map(fn (CarbonImmutable $date, string $field): string => __("user/sign-in-log.$field").': '.Preferences::current()->calendarDate($date))
            ->all();
    }
}
