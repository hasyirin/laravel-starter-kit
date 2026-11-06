<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class GeographySeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('address:seed', [], $this->command?->getOutput());
    }
}
