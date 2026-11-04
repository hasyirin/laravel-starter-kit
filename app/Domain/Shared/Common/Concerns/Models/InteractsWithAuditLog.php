<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Models;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait InteractsWithAuditLog
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->dontLogEmptyChanges()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(array_filter([...$this->getHidden(), $this->getKeyName(), $this->getCreatedAtColumn(), $this->getUpdatedAtColumn(), 'deleted_at']));
    }
}
