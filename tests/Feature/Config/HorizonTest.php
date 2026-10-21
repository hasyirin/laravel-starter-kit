<?php

declare(strict_types=1);

use Laravel\Horizon\ProvisioningPlan;

test('every supervisor times out before its queue connection retries the job', function (string $environment): void {
    foreach (ProvisioningPlan::get('test')->toSupervisorOptions()[$environment] as $supervisor => $options) {
        expect(config("queue.connections.{$options->connection}.retry_after"))
            ->toBeGreaterThan($options->timeout, "The {$supervisor} supervisor in {$environment} must time out before its connection retries the job.");
    }
})->with(['production', 'local']);
