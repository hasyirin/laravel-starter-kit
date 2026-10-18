<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait InteractsWithActivityLogs
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->dontLogEmptyChanges()
            ->logOnlyDirty()
            ->logFillable()
            ->logExcept($this->getHidden());
    }
}
