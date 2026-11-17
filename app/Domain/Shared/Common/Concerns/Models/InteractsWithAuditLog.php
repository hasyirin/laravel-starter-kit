<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Models;

use App\Domain\Shared\Common\Support\AuditLog;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\ModelStates\State;

trait InteractsWithAuditLog
{
    use LogsActivity {
        buildChanges as protected buildActivitylogChanges;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->dontLogEmptyChanges()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(array_filter([...$this->getHidden(), $this->getKeyName(), $this->getCreatedAtColumn(), $this->getUpdatedAtColumn(), 'deleted_at']))
            ->useAttributeRawValues(
                collect($this->getCasts())
                    ->filter(fn (string $cast): bool => is_subclass_of($cast, State::class))
                    ->keys()
                    ->all()
            );
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

    /**
     * Activitylog fills each old value that the model did not hold with null. An update then logs a change that did not happen.
     *
     * @return array<array-key, mixed>
     */
    protected function buildChanges(string $processingEvent): array
    {
        if ($processingEvent !== ActivityEvent::Updated->value) {
            return $this->buildActivitylogChanges($processingEvent);
        }

        $columns = collect($this->oldAttributes)
            ->merge($this->getChanges())
            ->keys();

        /** @var array<string, array<string, mixed>> $changes */
        $changes = $this->buildActivitylogChanges($processingEvent);

        return collect($changes)
            ->map(fn (array $values): array => collect($values)->only($columns)->all())
            ->all();
    }
}
