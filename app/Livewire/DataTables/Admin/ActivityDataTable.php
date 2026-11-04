<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Admin;

use App\Domain\Shared\Common\Support\Activities;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use App\Livewire\Pages\Admin\Controls\ActivityModal;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
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

abstract class ActivityDataTable extends DataTable
{
    /**
     * @return class-string<ActivityModal>
     */
    abstract protected function modal(): string;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->searchable()
            ->searchUsing(fn (Builder $query, string $search) => Activities::search($query, $search));
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
                ->state(fn (Activity $activity): string => Activities::eventLabel($activity))
                ->color(fn (Activity $activity): string => match ($activity->event) {
                    ActivityEvent::Created->value => 'success',
                    ActivityEvent::Deleted->value => 'danger',
                    ActivityEvent::Restored->value => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('causer_name')
                ->label((string) __('common/audit.causer'))
                ->state(fn (Activity $activity): ?string => Activities::causerName($activity))
                ->visible($this->showsCauser()),
            TextColumn::make('subject_label')
                ->label((string) __('common/audit.subject'))
                ->state(fn (Activity $activity): string => Activities::subjectLabel($activity))
                ->visible($this->showsSubject()),
            TextColumn::make('changes')
                ->label((string) __('common/audit.changes'))
                ->state(fn (Activity $activity): string => Activities::fieldLabels($activity)->implode(', '))
                ->limit(60),
            TimestampColumn::make('created_at')->label((string) __('common/audit.time')),
        ];
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        return array_values(array_filter([
            SelectFilter::make('event')
                ->label((string) __('common/audit.event'))
                ->options(fn (): array => Activities::events())
                ->multiple(),
            $this->showsSubject() ? SelectFilter::make('subject_type')
                ->label((string) __('common/audit.subject_type'))
                ->options(fn (): array => $this->subjectTypes())
                ->multiple() : null,
            Filter::make('created_at')
                ->label((string) __('common/audit.time'))
                ->schema([
                    DatePicker::make('from')->label((string) __('common/audit.from')),
                    DatePicker::make('until')->label((string) __('common/audit.until')),
                ])
                ->query(fn (Builder $query, array $data) => Activities::between($query, $data)),
        ]));
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()->action(fn (Activity $activity) => $this->dispatch('show', id: $activity->id)->to($this->modal())),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function subjectTypes(): array
    {
        return collect(Relation::morphMap())
            ->keys()
            ->mapWithKeys(fn (string $type): array => [$type => Activities::typeLabel($type)])
            ->all();
    }
}
