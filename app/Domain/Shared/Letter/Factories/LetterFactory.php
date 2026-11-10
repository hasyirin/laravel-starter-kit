<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Factories;

use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Letter>
 */
class LetterFactory extends Factory
{
    protected $model = Letter::class;

    public function definition(): array
    {
        return [
            'type' => array_key_first(config()->array('system.letters.types')),
            'source_type' => (new Organization)->getMorphClass(),
            'source_id' => Organization::factory(),
        ];
    }
}
