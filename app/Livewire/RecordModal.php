<?php

declare(strict_types=1);

namespace App\Livewire;

use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

/**
 * @template TRecord of Model
 *
 * @property-read TRecord $record
 */
abstract class RecordModal extends Modal
{
    #[Locked]
    public int|string|null $recordId = null;

    abstract public static function label(): string;

    /**
     * @return Builder<TRecord>|Relation<TRecord, *, *>
     */
    abstract protected function query(): Builder|Relation;

    abstract protected function fillForm(): void;

    abstract protected function persist(): void;

    public static function event(): string
    {
        return static::class.':saved';
    }

    /**
     * @return TRecord
     */
    #[Computed]
    public function record(): Model
    {
        // @phpstan-ignore return.type (Relation loses TRecord through its Builder mixin)
        return $this->recordId === null
            ? $this->query()->make()
            : $this->query()->findOrFail($this->recordId);
    }

    #[On('show')]
    public function show(int|string|null $id = null): void
    {
        $this->recordId = $id;
        unset($this->record);

        $this->authorize($this->record->exists ? 'view' : 'create', $this->record);

        $this->fillForm();

        parent::show();
    }

    public function isDisabled(?string $part = null): bool
    {
        return parent::isDisabled($part)
            || ($this->open && $this->cannot($this->record->exists ? 'update' : 'create', $this->record));
    }

    public function save(): void
    {
        abort_unless($this->open, 403);

        $this->ensureEnabled();

        $this->validate();

        $wasCreated = ! $this->record->exists;

        $this->persist();

        $this->recordId = null;
        unset($this->record);

        Flux::toast(variant: 'success', text: __($wasCreated ? 'common/records.created' : 'common/records.updated', ['record' => static::label()]));

        $this->dispatch(static::event());

        $this->close();
    }
}
