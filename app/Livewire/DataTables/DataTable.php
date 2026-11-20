<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use App\Domain\Shared\Common\Support\ModelKey;
use App\Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Tables\Columns\Column;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;

abstract class DataTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions {
        resolveTableAction as resolveFilamentTableAction;
    }
    use InteractsWithSchemas;
    use InteractsWithTable {
        callTableColumnAction as protected callFilamentTableColumnAction;
        resolveTableRecord as resolveFilamentTableRecord;
        updatedTableFilters as protected updatedFilamentTableFilters;
        updatedTableSearch as protected updatedFilamentTableSearch;
    }

    private const string EMPTY_OPTION = '__empty';

    private const string ABSENT_OPTION = '__absent';

    private const array TERNARY_VALUES = [true, false, 0, 1, '0', '1', ''];

    /**
     * @return array<int, Column>
     */
    abstract protected function columns(): array;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query(...))
            ->relationship($this->relationship(...))
            ->columns($this->columns())
            ->filters($this->filters())
            ->headerActions($this->headerActions())
            ->recordActions($this->actions() === [] ? [] : ActionGroup::make($this->actions()))
            ->recordAction($this->recordAction(...))
            ->recordUrl(fn (Model|array $record): ?string => $record instanceof Model ? $this->recordUrl($record) : null)
            ->paginated($this->paginated())
            ->enforcePaginationPageOptions()
            ->emptyStateHeading((string) __('common/records.none'))
            ->emptyStateDescription('');
    }

    public function placeholder(): string
    {
        return <<<'HTML'
            <div class="min-h-64 animate-pulse rounded-xl bg-zinc-100 dark:bg-white/5"></div>
            HTML;
    }

    #[Exposed]
    public function callTableColumnAction(string $name, mixed $recordKey): mixed
    {
        if (is_int($recordKey)) {
            $recordKey = (string) $recordKey;
        }

        if (! is_string($recordKey)) {
            return null;
        }

        return $this->callFilamentTableColumnAction($name, $recordKey);
    }

    #[Unexposed]
    public function updatedTableFilters(): void
    {
        $this->cleanFilters();
        $this->updatedFilamentTableFilters();
    }

    #[Unexposed]
    public function updatedTableDeferredFilters(): void
    {
        $this->cleanFilters();
    }

    #[Unexposed]
    public function updatedTableSearch(): void
    {
        if (! is_string($this->tableSearch)) {
            $this->tableSearch = '';
        }

        $this->updatedFilamentTableSearch();
    }

    /**
     * @return Builder<covariant Model>|null
     */
    protected function query(): ?Builder
    {
        return null;
    }

    /**
     * @return Relation<covariant Model, covariant Model, mixed>|null
     */
    protected function relationship(): ?Relation
    {
        return null;
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        return [];
    }

    /**
     * @return ActionGroup|array<int, Action|ActionGroup>
     */
    protected function headerActions(): ActionGroup|array
    {
        return [];
    }

    /**
     * @return array<int, Action|ActionGroup>
     */
    protected function actions(): array
    {
        return [];
    }

    /**
     * @param  Model|array<string, mixed>  $record
     */
    protected function recordAction(Model|array $record): ?string
    {
        $table = $this->getTable();
        $action = $table->getAction('view') ?? $table->getAction('edit');

        return ! $action instanceof Action || $action->getClone()->group(null)->record($record)->isHidden() ? null : $action->getName();
    }

    protected function recordUrl(Model $record): ?string
    {
        return null;
    }

    protected function paginated(): bool
    {
        return true;
    }

    /**
     * @return Model|array<array-key, mixed>|null
     */
    protected function resolveTableRecord(?string $key): Model|array|null
    {
        if ($key !== null && $this->getTable()->hasQuery() && ! ModelKey::valid($this->recordModel(), $key)) {
            return null;
        }

        return $this->resolveFilamentTableRecord($key);
    }

    /**
     * @param  array<string, mixed>  $action
     * @param  array<Action>  $parentActions
     */
    protected function resolveTableAction(array $action, array $parentActions): Action
    {
        $key = data_get($action, 'context.recordKey');

        throw_if(filled($key) && ! is_int($key) && ! is_string($key), ActionNotResolvableException::class, 'The record key is not text.');

        return $this->resolveFilamentTableAction($action, $parentActions);
    }

    private function recordModel(): Model
    {
        $table = $this->getTable();
        $relationship = $table->getRelationship();

        if ($relationship instanceof BelongsToMany && $table->allowsDuplicates()) {
            $pivot = $relationship->getPivotClass();

            return new $pivot;
        }

        /** @var class-string<Model> $model */
        $model = $table->getModel();

        return new $model;
    }

    private function cleanFilters(): void
    {
        $this->tableFilters = $this->cleanFilterStates($this->tableFilters);
        $this->tableDeferredFilters = $this->cleanFilterStates($this->tableDeferredFilters);
    }

    /**
     * @param  array<string, mixed>|null  $states
     * @return array<string, mixed>|null
     */
    private function cleanFilterStates(?array $states): ?array
    {
        if ($states === null) {
            return null;
        }

        $filters = $this->getTable()->getFilters(withHidden: true);

        return collect($states)
            ->map(fn (mixed $state, int|string $name): mixed => match (true) {
                ! isset($filters[$name]) => $state,
                $filters[$name] instanceof TernaryFilter => $this->cleanTernaryFilterState($filters[$name], $state),
                $filters[$name] instanceof SelectFilter => $this->cleanSelectFilterState($filters[$name], $state),
                default => is_array($state) ? $state : [],
            })
            ->all();
    }

    /**
     * @return array<array-key, mixed>
     */
    private function cleanTernaryFilterState(TernaryFilter $filter, mixed $state): array
    {
        if (! is_array($state)) {
            return ['value' => $filter->getDefaultState()];
        }

        $value = $state['value'] ?? null;

        if ($value === null || in_array($value, self::TERNARY_VALUES, true)) {
            return $state;
        }

        return [...$state, 'value' => $filter->getDefaultState()];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function cleanSelectFilterState(SelectFilter $filter, mixed $state): array
    {
        $model = $this->keyModel($filter);
        $absent = $model instanceof Model ? ModelKey::absentKey($model) : self::ABSENT_OPTION;
        $multiple = $filter->isMultiple();

        if (! is_array($state)) {
            return $multiple ? ['values' => [$absent]] : ['value' => $absent];
        }

        if ($multiple) {
            return [...$state, 'values' => $this->cleanValues($filter, $model, $state['values'] ?? null, $absent)];
        }

        return [...$state, 'value' => $this->cleanValue($filter, $model, $state['value'] ?? null, $absent)];
    }

    private function cleanValue(SelectFilter $filter, ?Model $model, mixed $value, int|string $absent): int|string|null
    {
        if ($value === null) {
            return null;
        }

        if (! is_int($value) && ! is_string($value)) {
            return $absent;
        }

        if (! $model instanceof Model || blank($value) || $this->isUsableKey($filter, $model, $value)) {
            return $value;
        }

        return $absent;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function cleanValues(SelectFilter $filter, ?Model $model, mixed $values, int|string $absent): ?array
    {
        if ($values === null) {
            return null;
        }

        if (! is_array($values) || collect($values)->contains(fn (mixed $value): bool => $value !== null && ! is_int($value) && ! is_string($value))) {
            return [$absent];
        }

        $filled = collect($values)->filter(fn (mixed $value): bool => filled($value));

        if (! $model instanceof Model) {
            return $filled->values()->all();
        }

        $usable = $filled->filter(fn (mixed $value): bool => $this->isUsableKey($filter, $model, $value));

        if ($filled->isNotEmpty() && $usable->isEmpty()) {
            return [$absent];
        }

        return $usable->values()->all();
    }

    private function isUsableKey(SelectFilter $filter, Model $model, mixed $value): bool
    {
        if ($value === self::EMPTY_OPTION && $filter->hasEmptyRelationshipOption()) {
            return true;
        }

        return ModelKey::valid($model, $value);
    }

    private function keyModel(SelectFilter $filter): ?Model
    {
        if (! $filter->hasRelationship()) {
            return null;
        }

        $relationship = $filter->getRelationship();
        $related = $relationship instanceof Relation ? $relationship->getRelated() : $relationship->getModel();
        $key = $filter->getQualifiedRelatedKeyNameForRelationship();

        if ($key !== null && $key !== $related->getQualifiedKeyName()) {
            return null;
        }

        return $related;
    }

    public function render(): View
    {
        return view('livewire.data-tables.data-table');
    }
}
