<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System\Letters;

use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Livewire\Concerns\ListsCarbonCopyTargets;
use App\Livewire\Forms\System\ComposeLetterForm;
use App\Livewire\Pages\Shared\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Compose extends Page
{
    use ListsCarbonCopyTargets;

    private const int PICKER_LIMIT = 20;

    public ComposeLetterForm $form;

    public string $start = 'blank';

    public string $sourceSearch = '';

    public ?string $sourceId = null;

    public ?string $letterId = null;

    public function mount(): void
    {
        $this->authorizeCompose();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function types(): array
    {
        return collect($this->form->types())
            ->mapWithKeys(fn (LetterType $type, string $key): array => [$key => str($key)->headline()->value()])
            ->all();
    }

    #[Computed]
    public function letterType(): ?LetterType
    {
        return $this->form->letterType();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function signers(): array
    {
        return $this->form->signers();
    }

    /**
     * @return array<int|string, string>
     */
    #[Computed]
    public function sources(): array
    {
        $type = $this->letterType;

        if (! $type instanceof LetterType || $this->start === 'blank') {
            return [];
        }

        $search = trim($this->sourceSearch);
        $query = $type->source()::query();

        if ($search !== '') {
            $query->where(fn (Builder $match): Builder => $match
                ->whereKey(ctype_digit($search) ? [(int) $search] : [])
                ->when($this->hasNameColumn($type), fn (Builder $named): Builder => $named->orWhereLike('name', "%$search%")));
        }

        $sources = $query->limit(self::PICKER_LIMIT)->get()->filter(fn (Model $source): bool => $this->can('view', $source));
        $picked = $this->pickedSource;

        if ($picked instanceof Model) {
            $sources->push($picked);
        }

        return $sources->unique(fn (Model $source): mixed => $source->getKey())->mapWithKeys(fn (Model $source): array => [(string) $source->getKey() => $this->sourceLabel($source)])->all();
    }

    /**
     * @return array<int|string, string>
     */
    #[Computed]
    public function letters(): array
    {
        $type = $this->letterType;
        $source = $this->start === 'letter' ? $this->pickedSource : null;

        if (! $type instanceof LetterType || ! $source instanceof Model) {
            return [];
        }

        return Letter::query()
            ->whereMorphedTo('source', $source)
            ->where('type', $this->form->type)
            ->latest('id')
            ->limit(self::PICKER_LIMIT)
            ->get()
            ->mapWithKeys(fn (Letter $letter): array => [$letter->id => trim(($letter->reference_number ?? "#{$letter->id}").' '.($letter->date?->toDateString() ?? ''))])
            ->all();
    }

    #[Computed]
    public function pickedSource(): ?Model
    {
        $type = $this->letterType;

        if (! $type instanceof LetterType || $this->sourceId === null || $this->sourceId === '') {
            return null;
        }

        $source = $type->source()::query()->findOrFail($this->sourceId);
        $this->authorize('view', $source);

        return $source;
    }

    public function updatedFormType(): void
    {
        $this->authorizeCompose();
        $this->resetStart();
    }

    public function updatedStart(): void
    {
        $this->authorizeCompose();
        $this->resetStart();
    }

    public function updatedSourceId(): void
    {
        $this->authorizeCompose();
        $this->letterId = null;
        unset($this->pickedSource, $this->sources, $this->letters);
        $this->form->startBlank();

        $source = $this->pickedSource;

        if ($source instanceof Model && $this->start === 'source') {
            $this->form->startFrom($source);
        }
    }

    public function updatedLetterId(): void
    {
        $this->authorizeCompose();
        $source = $this->pickedSource;

        if (! $source instanceof Model || $this->letterId === null) {
            return;
        }

        $letter = Letter::query()->whereMorphedTo('source', $source)->where('type', $this->form->type)->findOrFail((int) $this->letterId);

        $this->form->startFrom($source, $letter);
    }

    public function preview(): StreamedResponse
    {
        return $this->download(true);
    }

    public function print(): StreamedResponse
    {
        return $this->download(false);
    }

    private function download(bool $preview): StreamedResponse
    {
        $this->authorizeCompose();

        return $this->form->print($preview)->download();
    }

    private function authorizeCompose(): void
    {
        $this->authorize(config()->string('system.permissions.system.letters.compose'));
    }

    private function resetStart(): void
    {
        unset($this->letterType, $this->signers, $this->sources, $this->letters, $this->pickedSource);

        $this->sourceId = null;
        $this->letterId = null;
        $this->sourceSearch = '';
        $this->form->startBlank();
    }

    private function hasNameColumn(LetterType $type): bool
    {
        $model = new ($type->source());

        return Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), 'name');
    }

    private function sourceLabel(Model $source): string
    {
        $name = $source->getAttribute('name');
        $key = (string) $source->getKey();

        return is_string($name) && $name !== '' ? "$name (#$key)" : "#$key";
    }

    public function render(): View
    {
        return view('livewire.pages.system.letters.compose')->title(__('navigation.manual_letter'));
    }
}
