<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use App\Domain\Shared\Mail\Enums\MailStatus;
use App\Domain\Shared\Mail\Models\Mail;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\Pages\Shared\MailModal;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;

abstract class MailLogDataTable extends DataTable
{
    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('status')->label((string) __('mail/mails.status'))->badge(),
            TextColumn::make('recipient_email')
                ->label((string) __('mail/mails.recipient'))
                ->description(fn (Mail $mail): ?string => $mail->recipient_name)
                ->searchable(),
            TextColumn::make('subject')->label((string) __('mail/mails.subject'))->limit(60)->searchable(),
            IconColumn::make('opened')
                ->label((string) __('mail/mails.opened'))
                ->boolean()
                ->state(fn (Mail $mail): bool => $mail->opened_at !== null),
            IconColumn::make('clicked')
                ->label((string) __('mail/mails.clicked'))
                ->boolean()
                ->state(fn (Mail $mail): bool => $mail->clicked_at !== null),
            IconColumn::make('viewed')
                ->label((string) __('mail/mails.viewed'))
                ->boolean()
                ->state(fn (Mail $mail): bool => $mail->viewed_at !== null),
            TimestampColumn::make('created_at')->label((string) __('mail/mails.created_at')),
        ];
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        return [
            SelectFilter::make('status')
                ->label((string) __('mail/mails.status'))
                ->options(MailStatus::class)
                ->multiple(),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()->action(fn (Mail $mail) => $this->dispatch('show', id: $mail->id)->to(MailModal::class)),
        ];
    }
}
