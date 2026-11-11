<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Common\Support\AuditLog;
use App\Domain\Shared\Common\Support\ContextCauserResolver;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Support\CauserResolver;

class AuditLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CauserResolver::class, ContextCauserResolver::class);
    }

    public function boot(): void
    {
        LogActivityAction::beforeLogging(function (ActivityContract $activity): void {
            if (! $activity instanceof Activity) {
                return;
            }

            $activity->properties = AuditLog::context()->merge($activity->properties ?? []);
        });
    }
}
