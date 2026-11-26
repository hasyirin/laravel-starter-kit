<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use App\Domain\User\Actions\Session\EndAllSessions;
use App\Domain\User\Actions\Session\EndSession;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use App\Domain\User\Support\Impersonation;
use App\Domain\User\Support\UserAgent;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\Concerns\ConfirmsPassword;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;

abstract class SessionDataTable extends DataTable
{
    use ConfirmsPassword;

    abstract protected function user(): User;

    /**
     * @return Builder<UserSession>
     */
    protected function query(): Builder
    {
        return UserSession::query()
            ->ownedBy($this->user())
            ->whereNot(fn (Builder $query): Builder => $query->expired())
            ->with('user')
            ->latest('last_activity_at')
            ->latest('id');
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('browser')
                ->label((string) __('user/sessions.browser'))
                ->state(fn (UserSession $session): string => $this->browser($session))
                ->description(fn (UserSession $session): ?string => $this->mark($session)),
            TextColumn::make('ip_address')->label((string) __('user/sessions.ip_address')),
            TimestampColumn::make('last_activity_at')->label((string) __('user/sessions.last_activity_at')),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [$this->endAllAction()];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [$this->endAction()];
    }

    protected function endAction(): Action
    {
        return Action::make('end')
            ->label((string) __('user/sessions.end.button'))
            ->icon(Heroicon::ArrowRightStartOnRectangle)
            ->color('danger')
            ->hidden(fn (UserSession $session): bool => $this->current($session))
            ->requiresConfirmation()
            ->modalHeading((string) __('user/sessions.end.heading'))
            ->modalDescription((string) __('user/sessions.end.description'))
            ->modalSubmitActionLabel((string) __('user/sessions.end.button'))
            ->action(function (UserSession $session): void {
                if (! $this->passwordConfirmed()) {
                    return;
                }

                EndSession::make()->execute($session, Impersonation::personAtBrowser());

                Flux::toast(variant: 'success', text: __('user/sessions.end.ended'));
            });
    }

    protected function endAllAction(): Action
    {
        return Action::make('endAll')
            ->label((string) __("{$this->endAllKey()}.button"))
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading((string) __("{$this->endAllKey()}.heading"))
            ->modalDescription((string) __("{$this->endAllKey()}.description"))
            ->modalSubmitActionLabel((string) __("{$this->endAllKey()}.button"))
            ->action(function (): void {
                if (! $this->passwordConfirmed()) {
                    return;
                }

                EndAllSessions::make()->execute($this->user(), endedBy: Impersonation::personAtBrowser(), except: UserSession::current());

                Flux::toast(variant: 'success', text: __("{$this->endAllKey()}.ended"));
            });
    }

    protected function endAllKey(): string
    {
        return 'user/sessions.end_all';
    }

    protected function paginated(): bool
    {
        return false;
    }

    private function browser(UserSession $session): string
    {
        $userAgent = new UserAgent($session->user_agent);

        return (string) __('user/sessions.browser_on_platform', ['browser' => $userAgent->browser(), 'platform' => $userAgent->platform()]);
    }

    private function mark(UserSession $session): ?string
    {
        if ($this->current($session)) {
            return (string) __('user/sessions.current');
        }

        if ($session->impersonator_id === null) {
            return null;
        }

        return (string) __('user/sessions.impersonating', ['name' => $session->user?->name]);
    }

    private function current(UserSession $session): bool
    {
        return $session->id === session(UserSession::SESSION_KEY);
    }
}
