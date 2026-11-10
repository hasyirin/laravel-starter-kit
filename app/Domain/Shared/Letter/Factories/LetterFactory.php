<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Factories;

use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use LogicException;

/**
 * @extends Factory<Letter>
 */
class LetterFactory extends Factory
{
    protected $model = Letter::class;

    public function definition(): array
    {
        return [
            'type' => array_key_first(config()->array('system.letters.types')) ?? throw new LogicException('Register a letter type in system.letters.types, or pass a type, before making a letter.'),
            'source_type' => (new Organization)->getMorphClass(),
            'source_id' => Organization::factory(),
        ];
    }

    public function ofType(string $key): static
    {
        return $this->state(['type' => $key]);
    }
}
