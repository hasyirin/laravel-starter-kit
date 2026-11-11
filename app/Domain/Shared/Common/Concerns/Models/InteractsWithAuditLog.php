<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Models;

use App\Domain\Shared\Common\Support\AuditLog;
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

    public function auditNameColumn(): ?string
    {
        if (! $this->isFillable('name')) {
            return null;
        }

        return 'name';
    }

    public function auditLabel(): ?string
    {
        return AuditLog::attributeLabel($this);
    }
}
