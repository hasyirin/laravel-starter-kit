<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionMethod;
use ReflectionNamedType;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;

class UserActivities
{
    private const array NAME_ATTRIBUTES = ['label', 'name', 'title', 'email'];

    private const array DATE_CASTS = ['date', 'immutable_date'];

    private const array DATETIME_CASTS = ['datetime', 'immutable_datetime', 'custom_datetime', 'immutable_custom_datetime', 'timestamp'];

    /**
     * @return Builder<Activity>
     */
    public static function query(User $user, string $direction): Builder
    {
        return self::involving($user, by: $direction !== 'on', on: $direction === 'on')->latest('id');
    }

    public static function find(User $user, int $id): Activity
    {
        return self::involving($user, by: true, on: true)->findOrFail($id);
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
        return self::typeLabel((string) $activity->subject_type).' '.(self::recordName($activity->subject) ?? '#'.$activity->subject_id);
    }

    public static function fieldLabel(string $field): string
    {
        $key = collect(["common/inputs.$field", "validation.attributes.$field"])->first(fn (string $key): bool => trans()->has($key));

        return $key === null ? str($field)->replaceEnd('_id', '')->headline()->value() : (string) __($key);
    }

    /**
     * @return list<string>
     */
    public static function fieldLabels(Activity $activity): array
    {
        [$old, $new] = self::changeSets($activity);

        return array_map(fn (int|string $field): string => self::fieldLabel((string) $field), array_keys($new + $old));
    }

    /**
     * @return list<array{field: string, old: string, new: string}>
     */
    public static function changes(Activity $activity): array
    {
        [$old, $new] = self::changeSets($activity);
        $class = Relation::getMorphedModel((string) $activity->subject_type);
        $model = $class === null ? null : new $class;

        return array_map(function (int|string $field) use ($model, $old, $new): array {
            $field = (string) $field;
            $names = self::relatedNames($model, $field, [$old[$field] ?? null, $new[$field] ?? null]);

            return [
                'field' => self::fieldLabel($field),
                'old' => self::format($model, $field, $old[$field] ?? null, $names),
                'new' => self::format($model, $field, $new[$field] ?? null, $names),
            ];
        }, array_keys($new + $old));
    }

    /**
     * @return Builder<Activity>
     */
    private static function involving(User $user, bool $by, bool $on): Builder
    {
        return Activity::query()
            ->with(['causer', 'subject' => fn (MorphTo $subject): MorphTo => $subject->morphWith([Profile::class => ['user']])])
            ->where(function (Builder $query) use ($user, $by, $on): void {
                if ($by) {
                    $query->orWhere(fn (Builder $query): Builder => $query->whereMorphedTo('causer', $user));
                }

                if ($on) {
                    $query->orWhere(fn (Builder $query): Builder => $query->whereMorphedTo('subject', $user))
                        ->orWhere(fn (Builder $query): Builder => $query
                            ->where('subject_type', new Profile()->getMorphClass())
                            ->whereIn('subject_id', Profile::query()->select('id')->whereBelongsTo($user)));
                }
            });
    }

    /**
     * @return array{0: array<array-key, mixed>, 1: array<array-key, mixed>}
     */
    private static function changeSets(Activity $activity): array
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];

        return [
            is_array($changes['old'] ?? null) ? $changes['old'] : [],
            is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [],
        ];
    }

    private static function recordName(?Model $record): ?string
    {
        $attribute = collect(self::NAME_ATTRIBUTES)->first(fn (string $attribute): bool => (bool) $record?->hasAttribute($attribute));
        $name = $attribute === null ? null : $record?->getAttribute($attribute);

        return is_scalar($name) ? (string) $name : null;
    }

    /**
     * @param  list<mixed>  $ids
     * @return array<array-key, string>|null
     */
    private static function relatedNames(?Model $model, string $field, array $ids): ?array
    {
        $relation = self::belongsTo($model, $field);

        if (! $relation instanceof BelongsTo) {
            return null;
        }

        $names = [];
        $records = $relation->getQuery()->whereKey(array_values(array_filter($ids, fn (mixed $id): bool => is_int($id) || is_string($id))))->get();

        foreach ($records as $record) {
            $key = $record->getKey();
            $name = self::recordName($record);

            if ((is_int($key) || is_string($key)) && $name !== null) {
                $names[$key] = $name;
            }
        }

        return $names;
    }

    /**
     * @return BelongsTo<Model, Model>|null
     */
    private static function belongsTo(?Model $model, string $field): ?BelongsTo
    {
        $method = str($field)->replaceEnd('_id', '')->camel()->value();

        if (! $model instanceof Model || ! str_ends_with($field, '_id') || ! method_exists($model, $method)) {
            return null;
        }

        $type = new ReflectionMethod($model, $method)->getReturnType();

        if (! $type instanceof ReflectionNamedType || ! is_a($type->getName(), BelongsTo::class, true)) {
            return null;
        }

        $relation = Relation::noConstraints(fn (): mixed => $model->{$method}());

        /** @var BelongsTo<Model, Model>|null $belongsTo */
        $belongsTo = $relation instanceof BelongsTo && $relation->getForeignKeyName() === $field ? $relation : null;

        return $belongsTo;
    }

    /**
     * @param  array<array-key, string>|null  $names
     */
    private static function format(?Model $model, string $field, mixed $value, ?array $names): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => (string) __($value ? 'user/users.audit.yes' : 'user/users.audit.no'),
            $names !== null && (is_int($value) || is_string($value)) => $names[(string) $value] ?? '#'.$value,
            is_string($value) && (bool) $model?->hasCast($field, self::DATE_CASTS) => (string) Preferences::current()->calendarDate(CarbonImmutable::parse($value)),
            is_string($value) && ((bool) $model?->hasCast($field, self::DATETIME_CASTS) || in_array($field, $model?->getDates() ?? [], true)) => (string) Preferences::current()->datetime(CarbonImmutable::parse($value)),
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };
    }
}
