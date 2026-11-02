<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Local\Examples;

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Actor\Models\Action;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\EditAction;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\ActColumn;
use App\Filament\Tables\Columns\TimestampColumn;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Flux\Flux;
use Livewire\Attributes\Defer;

#[Defer]
class DataTable extends \App\Livewire\DataTables\DataTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->records(fn (): array => [
            1 => ['name' => 'Malaysia', 'code' => 'MY', 'created_at' => CarbonImmutable::parse('2026-01-15 09:30'), 'preparer' => $this->preparedBy('Ada Lovelace', 'Clerk', '2026-01-15 09:30')],
            2 => ['name' => 'Singapore', 'code' => 'SG', 'created_at' => CarbonImmutable::parse('2026-02-03 14:05'), 'preparer' => $this->preparedBy('Grace Hopper', null, '2026-02-03 14:05')],
            3 => ['name' => 'Indonesia', 'code' => 'ID', 'created_at' => CarbonImmutable::parse('2026-03-21 16:45'), 'preparer' => null],
        ]);
    }

    /**
     * @return CreateAction[]
     */
    protected function headerActions(): array
    {
        return [CreateAction::make()->action($this->showToast(...))];
    }

    /**
     * @return Column[]
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('code')->label((string) __('docs.examples.data_table.code'))->width(1),
            TextColumn::make('name')->label((string) __('docs.examples.data_table.name')),
            ActColumn::make('preparer')->label(Act::Prepare->byLabel()),
            TimestampColumn::make('created_at')->label((string) __('docs.examples.data_table.created_at')),
        ];
    }

    /**
     * @return \Filament\Actions\Action[]
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()->action($this->showToast(...)),
            EditAction::make()->action($this->showToast(...)),
            DeleteAction::make()
                ->hidden(false)
                ->action(fn () => Flux::toast(variant: 'success', text: __('common/records.deleted'))),
        ];
    }

    private function preparedBy(string $name, ?string $position, string $actedAt): Action
    {
        $user = new User()->forceFill(['name' => $name]);

        $profile = $position === null ? null : new Profile()
            ->setRelation('user', $user)
            ->setRelation('position', new Position()->forceFill(['name' => $position]));

        return new Action()
            ->forceFill(['name' => Act::Prepare, 'acted_at' => CarbonImmutable::parse($actedAt)])
            ->setRelation('profile', $profile)
            ->setRelation('actor', $user);
    }

    private function showToast(): void
    {
        Flux::toast(text: __('docs.examples.data_table.viewed'));
    }
}
