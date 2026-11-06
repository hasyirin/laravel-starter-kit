<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use App\Domain\Shared\Common\Support\AuditLog;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\Pages\Shared\AuditEntryModal;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;

abstract class AuditLogDataTable extends DataTable
{
    /**
     * @return class-string<AuditEntryModal>
     */
    abstract protected function modal(): string;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->searchable()
            ->searchUsing(fn (Builder $query, string $search) => AuditLog::search($query, $search));
    }

    protected function showsCauser(): bool
    {
        return true;
    }

    protected function showsSubject(): bool
    {
        return true;
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('event')
                ->label((string) __('common/audit.event'))
                ->badge()
                ->state(fn (Activity $entry): string => AuditLog::eventLabel($entry))
                ->color(fn (Activity $entry): string => match ($entry->event) {
                    ActivityEvent::Created->value => 'success',
                    ActivityEvent::Deleted->value => 'danger',
                    ActivityEvent::Restored->value => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('causer_name')
                ->label((string) __('common/audit.causer'))
                ->state(fn (Activity $entry): ?string => AuditLog::causerName($entry))
                ->visible($this->showsCauser()),
            TextColumn::make('impersonator_name')
                ->label((string) __('common/audit.impersonator'))
                ->state(fn (Activity $entry): ?string => AuditLog::impersonatorName($entry))
                ->toggleable(isToggledHiddenByDefault: true)
                ->visible($this->showsImpersonator()),
            TextColumn::make('subject_label')
                ->label((string) __('common/audit.subject'))
                ->state(fn (Activity $entry): string => AuditLog::subjectLabel($entry))
                ->visible($this->showsSubject()),
            TextColumn::make('changes')
                ->label((string) __('common/audit.changes'))
                ->state(fn (Activity $entry): string => AuditLog::fieldLabels($entry)->implode(', '))
                ->limit(60),
            TimestampColumn::make('created_at')->label((string) __('common/audit.time')),
        ];
    }

    protected function showsImpersonator(): bool
    {
        return false;
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        return collect([
            SelectFilter::make('event')
                ->label((string) __('common/audit.event'))
                ->options(fn (): array => AuditLog::events())
                ->multiple(),
            $this->showsCauser() ? $this->causerFilter() : null,
            $this->showsSubject() ? $this->subjectFilter() : null,
            $this->showsImpersonator() ? $this->impersonatorFilter() : null,
            Filter::make('created_at')
                ->label((string) __('common/audit.time'))
                ->schema([
                    DatePicker::make('from')->label((string) __('common/audit.from')),
                    DatePicker::make('until')->label((string) __('common/audit.until')),
                ])
                ->query(fn (Builder $query, array $data) => AuditLog::between($query, $data))
                ->indicateUsing(fn (array $data): array => $this->timeIndicators($data)),
        ])
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()->action(fn (Activity $entry) => $this->dispatch('show', id: $entry->id)->to($this->modal())),
        ];
    }

    private function causerFilter(): Filter
    {
        return $this->morphFilter('causer', (string) __('common/audit.causer'), fn (): array => AuditLog::causerTypes())
            ->query(fn (Builder $query, array $data) => AuditLog::causedBy($query, $data));
    }

    private function subjectFilter(): Filter
    {
        return $this->morphFilter('subject', (string) __('common/audit.subject'), fn (): array => $this->subjectTypes())
            ->query(fn (Builder $query, array $data) => AuditLog::about($query, $data));
    }

    /**
     * @param  Closure(): array<string, string>  $types
     */
    private function morphFilter(string $name, string $label, Closure $types): Filter
    {
        return Filter::make($name)
            ->label($label)
            ->indicateUsing(fn (array $data): ?string => $this->morphIndicator($label, $types, $data))
            ->schema([
                Select::make('type')
                    ->label($label)
                    ->options($types)
                    ->live()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('ids', [])),
                $this->recordSelect(fn (Get $get): mixed => $get('type'))
                    ->label(fn (Get $get): string => AuditLog::typeLabel((string) $get('type')))
                    ->visible(fn (Get $get): bool => AuditLog::nameColumn($get('type')) !== null),
            ]);
    }

    private function impersonatorFilter(): Filter
    {
        $user = new User()->getMorphClass();

        return Filter::make('impersonator')
            ->label((string) __('common/audit.impersonator'))
            ->schema([
                Toggle::make('only')->label((string) __('common/audit.impersonated_only')),
                $this->recordSelect(fn (): string => $user)->label((string) __('common/audit.impersonator')),
            ])
            ->query(fn (Builder $query, array $data) => AuditLog::impersonatedBy($query, $data))
            ->indicateUsing(fn (array $data): ?string => $this->impersonatorIndicator($user, $data));
    }

    /**
     * @param  Closure(Get): mixed  $type
     */
    private function recordSelect(Closure $type): Select
    {
        return Select::make('ids')
            ->multiple()
            ->searchable()
            ->getSearchResultsUsing(fn (Get $get, string $search): array => AuditLog::records($type($get), $search))
            ->getOptionLabelsUsing(fn (Get $get, array $values): array => AuditLog::recordNames($type($get), $values));
    }

    /**
     * @param  Closure(): array<string, string>  $types
     * @param  array<array-key, mixed>  $data
     */
    private function morphIndicator(string $label, Closure $types, array $data): ?string
    {
        $type = $data['type'] ?? null;

        if (! is_string($type) || $type === '') {
            return null;
        }

        $names = AuditLog::recordNames($type, is_array($data['ids'] ?? null) ? $data['ids'] : []);

        if ($names === []) {
            return $label.': '.($types()[$type] ?? AuditLog::typeLabel($type));
        }

        return $label.': '.implode(', ', $names);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function impersonatorIndicator(string $user, array $data): ?string
    {
        $names = AuditLog::recordNames($user, is_array($data['ids'] ?? null) ? $data['ids'] : []);

        if ($names !== []) {
            return __('common/audit.impersonator').': '.implode(', ', $names);
        }

        if (($data['only'] ?? false) !== true) {
            return null;
        }

        return (string) __('common/audit.impersonated_only');
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<string, string>
     */
    private function timeIndicators(array $data): array
    {
        return collect(['from', 'until'])
            ->filter(fn (string $field): bool => is_string($data[$field] ?? null) && $data[$field] !== '')
            ->mapWithKeys(fn (string $field): array => [$field => __("common/audit.$field").': '.Preferences::current()->calendarDate(CarbonImmutable::parse((string) $data[$field]))])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function subjectTypes(): array
    {
        return collect(Relation::morphMap())
            ->keys()
            ->mapWithKeys(fn (string $type): array => [$type => AuditLog::typeLabel($type)])
            ->all();
    }
}
