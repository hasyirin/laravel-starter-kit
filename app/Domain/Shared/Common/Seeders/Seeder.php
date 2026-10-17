<?php

namespace App\Domain\Shared\Common\Seeders;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TData
 */
abstract class Seeder extends \Illuminate\Database\Seeder
{
    /**
     * @var Collection<TKey, TData>
     */
    protected Collection $items;

    protected function before(): void {}

    /**
     * @return Collection<TKey, TData>
     */
    protected function collection(): Collection
    {
        return $this->items;
    }

    public function run(): void
    {
        $this->items = Collection::make($this->data());

        $this->before();

        foreach ($this->command->getOutput()->progressIterate($this->collection()) as $key => $data) {
            $this->each($key, $data);
        }

        $this->after();
    }

    /**
     * @param  TKey  $key
     * @param  TData  $data
     */
    abstract protected function each(string|int $key, mixed $data): void;

    protected function after(): void {}

    /**
     * @return Arrayable<TKey, TData>|array<TKey, TData>
     */
    abstract public function data(): Arrayable|array;
}
