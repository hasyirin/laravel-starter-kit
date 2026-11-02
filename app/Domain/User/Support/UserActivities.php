<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;

class UserActivities
{
    private const array NAME_ATTRIBUTES = ['label', 'name', 'title', 'email'];

    /**
     * @return Builder<Activity>
     */
    public static function query(User $user, string $direction): Builder
    {
        $query = Activity::query()
            ->with(['causer', 'subject' => fn (MorphTo $subject): MorphTo => $subject->morphWith([Profile::class => ['user']])])
            ->latest('id');

        if ($direction !== 'on') {
            return $query->whereMorphedTo('causer', $user);
        }

        return $query->where(fn (Builder $query): Builder => $query
            ->whereMorphedTo('subject', $user)
            ->orWhere(fn (Builder $query): Builder => $query
                ->where('subject_type', new Profile()->getMorphClass())
                ->whereIn('subject_id', Profile::query()->select('id')->whereBelongsTo($user))));
    }

    public static function find(User $user, int $id): Activity
    {
        return self::query($user, 'by')->find($id) ?? self::query($user, 'on')->findOrFail($id);
    }

    /**
     * @return array<string, string>
     */
    public static function events(): array
    {
        return collect(ActivityEvent::cases())
            ->mapWithKeys(fn (ActivityEvent $event): array => [$event->value => (string) __("user/users.audit.events.$event->value")])
            ->all();
    }

    public static function eventLabel(Activity $activity): string
    {
        return self::events()[$activity->event ?? ''] ?? $activity->description;
    }

    public static function typeLabel(string $type): string
    {
        $key = "user/users.audit.subjects.$type";

        return trans()->has($key) ? (string) __($key) : str($type)->headline()->value();
    }

    public static function subjectLabel(Activity $activity): string
    {
        $subject = $activity->subject;
        $nameAttribute = collect(self::NAME_ATTRIBUTES)->first(fn (string $attribute): bool => (bool) $subject?->hasAttribute($attribute));
        $name = $nameAttribute === null ? null : $subject?->getAttribute($nameAttribute);

        return self::typeLabel((string) $activity->subject_type).' '.(is_scalar($name) ? (string) $name : '#'.$activity->subject_id);
    }

    /**
     * @return list<array{field: string, old: string, new: string}>
     */
    public static function changes(Activity $activity): array
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];
        $new = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $old = is_array($changes['old'] ?? null) ? $changes['old'] : [];

        return array_map(fn (int|string $field): array => [
            'field' => (string) $field,
            'old' => self::format($old[$field] ?? null),
            'new' => self::format($new[$field] ?? null),
        ], array_keys($new + $old));
    }

    private static function format(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };
    }
}
