<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use App\Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\Column;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

abstract class DataTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

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

    public function render(): View
    {
        return view('livewire.data-tables.data-table');
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
}
