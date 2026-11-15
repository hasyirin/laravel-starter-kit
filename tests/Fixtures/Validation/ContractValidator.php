<?php

declare(strict_types=1);

namespace Tests\Fixtures\Validation;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\MessageBag;

class ContractValidator implements Validator
{
    /**
     * @param  array<string, array<int, string>>  $messages
     * @param  array<string, array<string, array<int, mixed>>>  $failed
     */
    public function __construct(private readonly array $messages, private readonly array $failed) {}

    /**
     * @return array<string, mixed>
     */
    public function validate(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return [];
    }

    public function fails(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<string, array<int, mixed>>>
     */
    public function failed(): array
    {
        return $this->failed;
    }

    /**
     * @param  string|array<int, string>  $attribute
     * @param  string|array<int, mixed>  $rules
     * @param  callable(mixed): bool  $callback
     */
    public function sometimes($attribute, $rules, callable $callback): static
    {
        return $this;
    }

    /**
     * @param  callable(self): void|string  $callback
     */
    public function after($callback): static
    {
        return $this;
    }

    public function errors(): MessageBag
    {
        return new MessageBag($this->messages);
    }

    public function getMessageBag(): MessageBag
    {
        return $this->errors();
    }
}
