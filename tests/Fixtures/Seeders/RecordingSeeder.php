<?php

declare(strict_types=1);

namespace Tests\Fixtures\Seeders;

use App\Domain\Shared\Common\Seeders\Seeder;

/**
 * @extends Seeder<string, string>
 */
class RecordingSeeder extends Seeder
{
    /**
     * @var list<array{array-key, string}>
     */
    public array $seeded = [];

    protected function each(string|int $key, mixed $data): void
    {
        $this->seeded[] = [$key, $data];
    }

    public function data(): array
    {
        return ['first' => 'one', 'second' => 'two'];
    }
}
