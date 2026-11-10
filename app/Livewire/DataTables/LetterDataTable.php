<?php

declare(strict_types=1);

namespace App\Livewire\DataTables;

use App\Domain\Shared\Letter\Actions\DeleteLetter;
use App\Domain\Shared\Letter\Actions\PrintLetter;
use App\Domain\Shared\Letter\Actions\UploadSignedScan;
use App\Domain\Shared\Letter\Enums\LetterMedia;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Media\Rules\Uploads;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use App\Filament\Actions\CreateAction;
use App\Filament\Actions\DeleteAction;
use App\Filament\Actions\EditAction;
use App\Filament\Tables\Columns\ActColumn;
use App\Livewire\Components\FileUpload;
use App\Livewire\Components\LetterModal;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Defer]
class LetterDataTable extends DataTable
{
    public const array SCAN_RULES = ['mimes:pdf,jpg,jpeg,png', 'max:10240'];

    #[Locked]
    public Model $source;

    public bool $showingScan = false;

    #[Locked]
    public ?int $scanLetterId = null;

    /** @var list<array{uuid: string, name: string, size: int, mime_type: string, preview?: bool, upload?: ?string}> */
    public array $scan = [];

    public function mount(): void
    {
        $this->authorize('view', $this->source);
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->defaultSort('id', 'desc');
    }

    public function openScan(int $id): void
    {
        $letter = $this->findLetter($id);

        $this->authorize('print', $letter);

        $this->scanLetterId = $letter->id;
        $this->scan = FileUpload::items($letter, LetterMedia::SignedScan);

        $this->showingScan = true;
    }

    public function saveScan(): void
    {
        $letter = $this->findLetter((int) $this->scanLetterId);

        $this->authorize('print', $letter);
        $this->validate(['scan' => ['array', 'max:1', new Uploads(self::SCAN_RULES)]]);

        UploadSignedScan::make()->execute($letter, $this->scan);

        $this->showingScan = false;
        Flux::toast(variant: 'success', text: __('letters/letters.scan_uploaded'));
    }

    /**
     * @return array<int|string, string>
     */
    protected function getListeners(): array
    {
        return [...parent::getListeners(), LetterModal::scopedEvent($this->source) => '$refresh'];
    }

    /**
     * @return Builder<Letter>
     */
    protected function query(): Builder
    {
        return Letter::query()
            ->whereMorphedTo('source', $this->source)
            ->with(['source', 'signature.profile.position', 'media']);
    }

    /**
     * @return array<int, Action>
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label((string) __('letters/letters.letter'))
                ->visible(fn (): bool => $this->can('create', new Letter()->source()->associate($this->source)))
                ->action(fn () => $this->dispatch('show', scope: LetterModal::scope($this->source))->to(LetterModal::class)),
        ];
    }

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('type')
                ->label((string) __('letters/letters.type'))
                ->formatStateUsing(fn (string $state): string => str($state)->headline()->value()),
            TextColumn::make('reference_number')->label((string) __('letters/letters.reference_number'))->searchable(),
            TextColumn::make('date')
                ->label((string) __('letters/letters.date'))
                ->formatStateUsing(fn (?DateTimeInterface $state): ?string => Preferences::current()->calendarDate($state)),
            ActColumn::make('signature')->label((string) __('letters/letters.signer')),
            IconColumn::make('signed_scan')
                ->label((string) __('letters/letters.signed_scan'))
                ->boolean()
                ->state(fn (Letter $letter): bool => $letter->hasMedia(LetterMedia::SignedScan->value)),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function actions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Letter $letter): bool => $this->canAny(['update', 'print'], $letter))
                ->action(fn (Letter $letter) => $this->dispatch('show', id: $letter->id, scope: LetterModal::scope($this->source))->to(LetterModal::class)),

            Action::make('preview')
                ->label((string) __('letters/letters.preview'))
                ->icon('heroicon-m-document-magnifying-glass')
                ->color('gray')
                ->visible(fn (Letter $letter): bool => $this->can('print', $letter))
                ->url(fn (Letter $letter): string => route('letters.preview', $letter))
                ->openUrlInNewTab(),

            Action::make('print')
                ->label((string) __('letters/letters.print'))
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->visible(fn (Letter $letter): bool => $this->can('print', $letter))
                ->action(fn (Letter $letter): ?StreamedResponse => $this->print($letter)),

            Action::make('upload_scan')
                ->label((string) __('letters/letters.upload_scan'))
                ->icon('heroicon-m-arrow-up-tray')
                ->color('gray')
                ->visible(fn (Letter $letter): bool => $this->can('print', $letter))
                ->action(fn (Letter $letter) => $this->openScan($letter->id)),

            Action::make('prints')
                ->label((string) __('letters/letters.kept_prints'))
                ->icon('heroicon-m-archive-box')
                ->color('gray')
                ->visible(fn (Letter $letter): bool => $this->can('view', $letter) && $letter->getMedia(LetterMedia::Prints->value)->isNotEmpty())
                ->modalContent(fn (Letter $letter): View => view('livewire.data-tables.letter-prints', ['prints' => $this->prints($letter)]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel((string) __('common/buttons.close')),

            DeleteAction::make()
                ->through(DeleteLetter::class)
                ->visible(fn (Letter $letter): bool => $this->can('delete', $letter)),
        ];
    }

    private function print(Letter $letter): ?StreamedResponse
    {
        $this->authorize('print', $letter);

        /** @var User $user */
        $user = auth()->user();

        try {
            return PrintLetter::make()->execute($letter, $user)->download();
        } catch (ValidationException $validationException) {
            Flux::toast(variant: 'danger', text: (string) (collect($validationException->errors())->flatten()->first() ?? __('letters/letters.print_failed')));

            return null;
        }
    }

    private function findLetter(int $id): Letter
    {
        return Letter::query()->whereMorphedTo('source', $this->source)->findOrFail($id);
    }

    /**
     * @return list<array{name: string, printer: string, printed_at: ?string, url: string}>
     */
    private function prints(Letter $letter): array
    {
        $media = $letter->getMedia(LetterMedia::Prints->value);
        $printers = User::query()->whereKey($media->map(fn (Media $print): mixed => $print->getCustomProperty('user_id'))->filter()->all())->pluck('name', 'id');

        return array_values($media->map(function (Media $print) use ($printers): array {
            $printedAt = $print->getCustomProperty('printed_at');

            return [
                'name' => $print->file_name,
                'printer' => (string) $printers->get((int) $print->getCustomProperty('user_id'), ''),
                'printed_at' => is_string($printedAt) ? Preferences::current()->datetime(CarbonImmutable::parse($printedAt)) : null,
                'url' => $print->getUrl(),
            ];
        })->all());
    }

    public function render(): View
    {
        return view('livewire.data-tables.letter-data-table');
    }
}
