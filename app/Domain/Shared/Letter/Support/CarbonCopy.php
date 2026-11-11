<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

final readonly class CarbonCopy
{
    public function __construct(
        public string $name,
        public ?string $type = null,
        public ?int $id = null,
    ) {}

    /**
     * @param  array{name: string, type?: string|null, id?: int|null}  $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values['name'], $values['type'] ?? null, $values['id'] ?? null);
    }

    /**
     * @return array{name: string, type: string|null, id: int|null}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'id' => $this->id,
        ];
    }
}
