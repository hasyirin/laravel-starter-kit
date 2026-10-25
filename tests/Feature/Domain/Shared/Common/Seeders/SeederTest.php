<?php

declare(strict_types=1);

use Tests\Fixtures\Seeders\RecordingSeeder;

test('seeding without a console command still seeds every row', function (): void {
    $seeder = new RecordingSeeder;

    $seeder->run();

    expect($seeder->seeded)->toBe([['first', 'one'], ['second', 'two']]);
});
