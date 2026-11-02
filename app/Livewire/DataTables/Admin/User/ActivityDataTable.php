<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Admin\User;

use App\Domain\User\Models\User;
use App\Domain\User\Support\UserActivities;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use App\Livewire\Pages\Admin\Controls\User\ActivityModal;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;

#[Defer]
class ActivityDataTable extends DataTable
{
    #[Locked]
    public int $userId;

    #[Locked]
    public string $direction = 'by';

    public function mount(): void
    {
        $this->authorize('view', [$this->user, 'activities']);
    }

    #[Computed]
    public function user(): User
    {
        return User::query()->findOrFail($this->userId);
    }

    /**
     * @return Builder<Activity>
     */
    protected function query(): Builder
    {
        return UserActivities::query($this->user, $this->direction);
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return array_values(array_filter([
            TimestampColumn::make('created_at')->label((string) __('user/users.audit.time')),
            TextColumn::make('event')
                ->label((string) __('user/users.audit.event'))
                ->badge()
                ->state(fn (Activity $activity): string => UserActivities::eventLabel($activity))
                ->color(fn (Activity $activity): string => match ($activity->event) {
                    ActivityEvent::Created->value => 'success',
                    ActivityEvent::Deleted->value => 'danger',
                    ActivityEvent::Restored->value => 'warning',
                    default => 'gray',
                }),
            $this->direction === 'on'
                ? TextColumn::make('causer_name')
                    ->label((string) __('user/users.audit.causer'))
                    ->state(fn (Activity $activity): ?string => $activity->causer instanceof User ? $activity->causer->name : null)
                : null,
            TextColumn::make('subject_label')
                ->label((string) __('user/users.audit.subject'))
                ->state(fn (Activity $activity): string => UserActivities::subjectLabel($activity)),
            TextColumn::make('changes')
                ->label((string) __('user/users.audit.changes'))
                ->state(fn (Activity $activity): string => implode(', ', array_column(UserActivities::changes($activity), 'field')))
                ->limit(60),
        ]));
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        if ($this->direction === 'on') {
            return [];
        }

        return [
            SelectFilter::make('event')
                ->label((string) __('user/users.audit.event'))
                ->options(UserActivities::events()),
            SelectFilter::make('subject_type')
                ->label((string) __('user/users.audit.subject_type'))
                ->options(collect(array_keys(Relation::morphMap()))->mapWithKeys(fn (string $type): array => [$type => UserActivities::typeLabel($type)])->all()),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()->action(fn (Activity $activity) => $this->dispatch('show', id: $activity->id)->to(ActivityModal::class)),
        ];
    }
}
