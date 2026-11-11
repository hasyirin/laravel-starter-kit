<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Filament\Actions\ViewAction;
use App\Filament\Tables\Columns\TimestampColumn;
use App\Livewire\Pages\Shared\MessageModal;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;

abstract class MessageLogDataTable extends DataTable
{
    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('status')->label((string) __('message/messages.status'))->badge(),
            TextColumn::make('recipient_address')
                ->label((string) __('message/messages.recipient'))
                ->description(fn (Message $message): ?string => $message->recipient_name)
                ->searchable(),
            TextColumn::make('subject')->label((string) __('message/messages.subject'))->limit(60)->searchable(),
            IconColumn::make('opened')
                ->label((string) __('message/messages.opened'))
                ->boolean()
                ->state(fn (Message $message): bool => $message->opened_at !== null),
            IconColumn::make('clicked')
                ->label((string) __('message/messages.clicked'))
                ->boolean()
                ->state(fn (Message $message): bool => $message->clicked_at !== null),
            IconColumn::make('viewed')
                ->label((string) __('message/messages.viewed'))
                ->boolean()
                ->state(fn (Message $message): bool => $message->viewed_at !== null),
            TimestampColumn::make('created_at')->label((string) __('message/messages.created_at')),
        ];
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected function filters(): array
    {
        return [
            SelectFilter::make('status')
                ->label((string) __('message/messages.status'))
                ->options(MessageStatus::class)
                ->multiple(),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            ViewAction::make()->action(fn (Message $message) => $this->dispatch('show', id: $message->id)->to(MessageModal::class)),
        ];
    }
}
