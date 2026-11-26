<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Jobs\ApplyApiLogRetention;
use App\Domain\Shared\Common\Jobs\ApplyAuditLogRetention;
use App\Domain\Shared\Message\Jobs\ApplyMessageLogRetention;
use App\Domain\User\Jobs\ApplySignInLogRetention;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::onOneServer()->group(function (): void {
    Schedule::command('horizon:snapshot')->everyFiveMinutes();
    Schedule::job(new ApplyAuditLogRetention)->daily();
    Schedule::job(new ApplyMessageLogRetention)->daily();
    Schedule::job(new ApplyApiLogRetention)->daily();
    Schedule::job(new ApplySignInLogRetention)->daily();
    Schedule::command('queue:prune-failed --hours=168')->daily();
    Schedule::command('auth:clear-resets')->daily();
});
