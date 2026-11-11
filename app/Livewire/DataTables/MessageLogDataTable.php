<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\User\Support\Preferences;
use App\Filament\Actions\ViewAction;
use App\Livewire\Pages\Shared\MessageModal;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Filament\Actions\Action;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

use function Filament\Support\generate_icon_html;

abstract class MessageLogDataTable extends DataTable
{
    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('recipient_address')
                ->label((string) __('message/messages.recipient'))
                ->description(fn (Message $message): ?string => $message->recipient_name)
                ->searchable(),
            TextColumn::make('channel')
                ->label((string) __('message/messages.channel'))
                ->badge()
                ->color('gray')
                ->formatStateUsing(fn (Message $message): string => $message->channel_label),
            TextColumn::make('status')->label((string) __('message/messages.status'))->badge(),
            TextColumn::make('resend')
                ->label($this->hinted((string) __('message/messages.columns.resend')))
                ->headerTooltip((string) __('message/messages.hints.resend'))
                ->badge()
                ->color(fn (string $state): string => $state === 'resend' ? 'info' : 'gray')
                ->state(fn (Message $message): array => $this->resendMarks($message))
                ->formatStateUsing(fn (string $state, Message $message): string => match ($state) {
                    'resend' => (string) __('message/messages.marks.resend'),
                    default => trans_choice('message/messages.marks.resent', (int) $message->resends_count, ['count' => $message->resends_count]),
                }),
            TextColumn::make('subject')
                ->label((string) __('message/messages.columns.content'))
                ->state(fn (Message $message): ?string => $message->subject ?? $message->text_body)
                ->limit(60)
                ->wrap()
                ->searchable(['subject', 'text_body'])
                ->toggleable(isToggledHiddenByDefault: true),
            IconColumn::make('opened')
                ->label($this->hinted((string) __('message/messages.columns.opened')))
                ->headerTooltip((string) __('message/messages.hints.opened'))
                ->boolean()
                ->state(fn (Message $message): ?bool => $this->sign($message, $message->opened_at)),
            IconColumn::make('clicked')
                ->label($this->hinted((string) __('message/messages.columns.clicked')))
                ->headerTooltip((string) __('message/messages.hints.clicked'))
                ->boolean()
                ->state(fn (Message $message): ?bool => $this->sign($message, $message->clicked_at)),
            IconColumn::make('viewed')
                ->label($this->hinted((string) __('message/messages.viewed')))
                ->headerTooltip((string) __('message/messages.hints.viewed'))
                ->boolean()
                ->state(fn (Message $message): ?bool => $this->sign($message, $message->viewed_at)),
            $this->time('created_at', (string) __('message/messages.started_at'), (string) __('message/messages.hints.started')),
            $this->time('sent_at', (string) __('message/messages.sent_at'), (string) __('message/messages.hints.sent')),
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
            SelectFilter::make('channel')
                ->label((string) __('message/messages.channel'))
                ->options(fn (): array => (array) __('message/messages.channels'))
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

    /**
     * The date stands above the time, so the two time columns stay narrow.
     */
    private function time(string $column, string $label, string $hint): TextColumn
    {
        return TextColumn::make($column)
            ->label($this->hinted($label))
            ->headerTooltip($hint)
            ->formatStateUsing(fn (?DateTimeInterface $state): ?string => Preferences::current()->date($state))
            ->description(fn (?DateTimeInterface $state): ?string => Preferences::current()->time($state))
            ->alignEnd();
    }

    /**
     * @return Builder<Message>
     */
    protected function messages(): Builder
    {
        return Message::query()->withCount('resends')->latest('id');
    }

    /**
     * @return array<int, string>
     */
    private function resendMarks(Message $message): array
    {
        return collect([
            'resend' => $message->resend_of_id !== null,
            'resent' => $message->resends_count > 0,
        ])->filter()->keys()->all();
    }

    private function hinted(string $label): HtmlString
    {
        $icon = generate_icon_html(Heroicon::OutlinedInformationCircle, size: IconSize::Small);

        return new HtmlString('<span class="inline-flex items-center gap-1">'.e($label).$icon?->toHtml().'</span>');
    }

    private function sign(Message $message, ?CarbonImmutable $at): ?bool
    {
        if (! $message->tracked) {
            return null;
        }

        return $at instanceof CarbonImmutable;
    }
}
