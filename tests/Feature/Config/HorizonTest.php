<?php

declare(strict_types=1);

use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\ProvisioningPlan;

test('every supervisor times out before its queue connection retries the job', function (string $environment): void {
    foreach (ProvisioningPlan::get('test')->toSupervisorOptions()[$environment] as $supervisor => $options) {
        expect(config("queue.connections.{$options->connection}.retry_after"))
            ->toBeGreaterThan($options->timeout, "The {$supervisor} supervisor in {$environment} must time out before its connection retries the job.");
    }
})->with(['production', 'local', '*']);

test('an environment without its own entry still starts every supervisor', function (): void {
    $commands = new class implements HorizonCommandQueue
    {
        /** @var list<string> */
        public array $supervisors = [];

        public function push($name, $command, array $options = []): void
        {
            $this->supervisors[] = $options['name'];
        }

        public function pending($name): array
        {
            return [];
        }

        public function flush($name): void {}
    };
    app()->instance(HorizonCommandQueue::class, $commands);

    ProvisioningPlan::get('test')->deploy('staging');

    expect($commands->supervisors)->toBe(['test:supervisor-default', 'test:supervisor-media']);
});

test('the dashboard is in the admin area', function (): void {
    expect(route('horizon.index'))->toBe(url('admin/horizon'));
});
