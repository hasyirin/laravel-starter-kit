<?php

declare(strict_types=1);

namespace App\Livewire\DataTables\Settings;

use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\DataTables\DataTable;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SignInLogDataTable extends DataTable
{
    private const int DAYS = 30;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->heading((string) __('user/settings.sign_in_log.heading'))
            ->description((string) __('user/settings.sign_in_log.description', ['days' => self::DAYS]));
    }

    /**
     * @return Builder<SignInEntry>
     */
    protected function query(): Builder
    {
        return SignInEntry::query()
            ->whereBelongsTo($this->user())
            ->whereIn('event', [SignInEvent::SignIn, SignInEvent::FailedSignIn])
            ->where('created_at', '>=', now()->subDays(self::DAYS))
            ->latest('id');
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TimestampColumn::make('created_at')->label((string) __('user/sign-in-log.time'))->alignStart(),
            TextColumn::make('result')->label((string) __('user/sign-in-log.result'))->badge(),
            TextColumn::make('method')->label((string) __('user/sign-in-log.method')),
            TextColumn::make('ip_address')->label((string) __('user/sign-in-log.ip_address')),
            TextColumn::make('user_agent')
                ->label((string) __('user/sign-in-log.user_agent'))
                ->limit(60)
                ->wrap(),
        ];
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
