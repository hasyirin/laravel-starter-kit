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

    protected function acrossOrganizations(): bool
    {
        return true;
    }

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
            ->map(fn (LetterType $type): string => $type->label())
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
            $key = $this->sourceKey($query->getModel(), $search);

            $query->where(fn (Builder $match): Builder => $match
                ->whereKey(is_int($key) ? [$key] : [])
                ->when($this->searchesName, fn (Builder $named): Builder => $named->orWhereLike('name', "%$search%")));
        }

        $sources = $query
            ->lazyById(self::PICKER_LIMIT * 5)
            ->filter(fn (Model $source): bool => $this->can('view', $source))
            ->take(self::PICKER_LIMIT)
            ->collect();
        $picked = $this->pickedSource;

        if ($picked instanceof Model) {
            $sources->push($picked);
        }

        return $sources
            ->unique(fn (Model $source): mixed => $source->getKey())
            ->mapWithKeys(fn (Model $source): array => [(string) $source->getKey() => $this->sourceLabel($source)])
            ->all();
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
            ->mapWithKeys(function (Letter $letter): array {
                $reference = $letter->reference_number ?? "#{$letter->id}";
                $date = $letter->date?->toDateString() ?? '';

                return [$letter->id => trim($reference.' '.$date)];
            })
            ->all();
    }

    #[Computed]
    public function searchesName(): bool
    {
        $type = $this->letterType;

        if (! $type instanceof LetterType) {
            return false;
        }

        $model = new ($type->source());

        return Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), 'name');
    }

    #[Computed]
    public function pickedSource(): ?Model
    {
        $type = $this->letterType;

        if (! $type instanceof LetterType || $this->sourceId === null || $this->sourceId === '') {
            return null;
        }

        $query = $type->source()::query();
        $key = $this->sourceKey($query->getModel(), $this->sourceId);

        abort_if($key === null, 404);

        $source = $query->findOrFail($key);
        $this->authorize('view', $source);

        return $source;
    }

    private function sourceKey(Model $model, string $value): int|string|null
    {
        if ($model->getKeyType() !== 'int') {
            return $value;
        }

        $key = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $key === false ? null : $key;
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

        if (! $source instanceof Model || $this->letterId === null || $this->letterId === '') {
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
        unset($this->letterType, $this->signers, $this->sources, $this->letters, $this->pickedSource, $this->searchesName);

        $this->sourceId = null;
        $this->letterId = null;
        $this->sourceSearch = '';
        $this->form->startBlank();
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
