<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use App\Domain\User\Models\SignInEntry;
use App\Filament\Tables\Columns\TimestampColumn;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

abstract class SignInLogDataTable extends DataTable
{
    /**
     * @return Builder<SignInEntry>
     */
    protected function query(): Builder
    {
        return SignInEntry::query()
            ->with(['user', 'impersonator'])
            ->latest('id');
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TimestampColumn::make('created_at')->label((string) __('user/sign-in-log.time'))->alignStart(),
            TextColumn::make('user.name')
                ->label((string) __('user/sign-in-log.user'))
                ->placeholder((string) __('user/sign-in-log.no_user')),
            TextColumn::make('event')->label((string) __('user/sign-in-log.event'))->badge()->color('gray'),
            TextColumn::make('result')->label((string) __('user/sign-in-log.result'))->badge(),
            TextColumn::make('method')->label((string) __('user/sign-in-log.method')),
            IconColumn::make('two_factor')->label((string) __('user/sign-in-log.two_factor'))->boolean(),
            TextColumn::make('impersonator.name')->label((string) __('user/sign-in-log.impersonator')),
            TextColumn::make('ip_address')->label((string) __('user/sign-in-log.ip_address')),
            TextColumn::make('user_agent')
                ->label((string) __('user/sign-in-log.user_agent'))
                ->limit(60)
                ->wrap()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('trace_id')
                ->label((string) __('user/sign-in-log.trace_id'))
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
