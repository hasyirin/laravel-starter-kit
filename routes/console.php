<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Jobs\ApplyAuditLogRetention;
use App\Domain\Shared\Mail\Jobs\ApplyMailLogRetention;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::onOneServer()->group(function (): void {
    Schedule::command('horizon:snapshot')->everyFiveMinutes();
    Schedule::job(new ApplyAuditLogRetention)->daily();
    Schedule::job(new ApplyMailLogRetention)->daily();
    Schedule::command('queue:prune-failed --hours=168')->daily();
    Schedule::command('auth:clear-resets')->daily();
});
