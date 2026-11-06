<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Support;

use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use ReflectionMethod;
use ReflectionNamedType;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;

class AuditLog
{
    private const array NAME_ATTRIBUTES = ['label', 'name', 'title', 'email'];

    private const array DATE_CASTS = ['date', 'immutable_date'];

    private const array DATETIME_CASTS = ['datetime', 'immutable_datetime', 'custom_datetime', 'immutable_custom_datetime', 'timestamp'];

    /**
     * @param  Builder<Activity>  $query
     */
    public static function search(Builder $query, string $search): void
    {
        $term = str($search)->trim()->lower()->value();

        $events = collect(self::events())
            ->filter(fn (string $label): bool => str($label)->lower()->contains($term))
            ->keys();

        $types = collect(Relation::morphMap())
            ->keys()
            ->filter(fn (string $type): bool => str(self::typeLabel($type))->lower()->contains($term));

        $query->where(fn (Builder $query): Builder => $query
            ->whereIn('event', $events)
            ->orWhereIn('subject_type', $types)
            ->orWhereHasMorph('causer', [User::class], fn (Builder $causer): Builder => $causer->whereLike('name', "%$term%"))
            ->orWhereLike(new Expression('CAST(attribute_changes AS TEXT)'), "%$term%"));
    }

    /**
     * @param  Builder<Activity>  $query
     * @param  array<array-key, mixed>  $dates
     */
    public static function between(Builder $query, array $dates): void
    {
        $timezone = Preferences::current()->timezone;
        $from = $dates['from'] ?? null;
        $until = $dates['until'] ?? null;

        if (is_string($from) && $from !== '') {
            $query->where('created_at', '>=', CarbonImmutable::parse($from, $timezone)->startOfDay()->setTimezone(config()->string('app.timezone')));
        }

        if (is_string($until) && $until !== '') {
            $query->where('created_at', '<=', CarbonImmutable::parse($until, $timezone)->endOfDay()->setTimezone(config()->string('app.timezone')));
        }
    }

    /**
     * @return array<string, string>
     */
    public static function events(): array
    {
        return collect(ActivityEvent::cases())
            ->mapWithKeys(fn (ActivityEvent $event): array => [$event->value => (string) __("common/audit.events.$event->value")])
            ->all();
    }

    public static function eventLabel(Activity $entry): string
    {
        return self::events()[$entry->event ?? ''] ?? $entry->description;
    }

    public static function typeLabel(string $type): string
    {
        $key = "common/audit.subjects.$type";

        if (! trans()->has($key)) {
            return str($type)->headline()->value();
        }

        return (string) __($key);
    }

    public static function subjectLabel(Activity $entry): string
    {
        return self::typeLabel((string) $entry->subject_type).' '.(self::recordName($entry->subject) ?? '#'.$entry->subject_id);
    }

    public static function causerName(Activity $entry): ?string
    {
        if (! $entry->causer instanceof User) {
            return null;
        }

        return $entry->causer->name;
    }

    public static function impersonatorName(Activity $entry): ?string
    {
        $id = $entry->getProperty('impersonator_id');

        if ($id === null) {
            return null;
        }

        $name = User::withTrashed()->whereKey($id)->value('name');

        return is_string($name) ? $name : null;
    }

    public static function summary(Activity $entry): string
    {
        return collect([
            self::eventLabel($entry),
            self::causerName($entry),
            Preferences::current()->datetime($entry->created_at),
        ])
            ->filter()
            ->implode(' · ');
    }

    public static function fieldLabel(string $field): string
    {
        $key = collect(["common/inputs.$field", "validation.attributes.$field"])->first(fn (string $key): bool => trans()->has($key));

        if ($key === null) {
            return str($field)->replaceEnd('_id', '')->headline()->value();
        }

        return (string) __($key);
    }

    /**
     * @return Collection<int, string>
     */
    public static function fieldLabels(Activity $entry): Collection
    {
        [$old, $new] = self::changeSets($entry);

        return self::fieldsOf($old, $new)->map(fn (string $field): string => self::fieldLabel($field));
    }

    /**
     * @return Collection<int, array{field: string, old: string, new: string}>
     */
    public static function changes(Activity $entry): Collection
    {
        [$old, $new] = self::changeSets($entry);
        $model = self::subjectModel($entry);

        return self::fieldsOf($old, $new)
            ->map(fn (string $field): array => self::change($model, $field, $old[$field] ?? null, $new[$field] ?? null));
    }

    /**
     * @return array{0: array<array-key, mixed>, 1: array<array-key, mixed>}
     */
    private static function changeSets(Activity $entry): array
    {
        $changes = $entry->attribute_changes?->toArray() ?? [];

        return [
            is_array($changes['old'] ?? null) ? $changes['old'] : [],
            is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [],
        ];
    }

    /**
     * @param  array<array-key, mixed>  $old
     * @param  array<array-key, mixed>  $new
     * @return Collection<int, string>
     */
    private static function fieldsOf(array $old, array $new): Collection
    {
        return collect($new)
            ->union($old)
            ->keys()
            ->map(fn (int|string $field): string => (string) $field);
    }

    /**
     * @return array{field: string, old: string, new: string}
     */
    private static function change(?Model $model, string $field, mixed $old, mixed $new): array
    {
        if (self::isList($old) && self::isList($new)) {
            $old = collect($old);
            $new = collect($new);

            return [
                'field' => self::fieldLabel($field),
                'old' => $old->diff($new)->implode(', '),
                'new' => $new->diff($old)->implode(', '),
            ];
        }

        $names = self::relatedNames($model, $field, [$old, $new]);

        return [
            'field' => self::fieldLabel($field),
            'old' => self::format($model, $field, $old, $names),
            'new' => self::format($model, $field, $new, $names),
        ];
    }

    /**
     * @phpstan-assert-if-true list<scalar>|null $value
     */
    private static function isList(mixed $value): bool
    {
        return $value === null || (is_array($value) && array_is_list($value) && collect($value)->every(fn (mixed $item): bool => is_scalar($item)));
    }

    private static function subjectModel(Activity $entry): ?Model
    {
        $class = Relation::getMorphedModel((string) $entry->subject_type);

        if ($class === null) {
            return null;
        }

        return new $class;
    }

    private static function recordName(?Model $record): ?string
    {
        $attribute = collect(self::NAME_ATTRIBUTES)->first(fn (string $attribute): bool => (bool) $record?->hasAttribute($attribute));

        if ($attribute === null) {
            return null;
        }

        $name = $record?->getAttribute($attribute);

        if (! is_scalar($name)) {
            return null;
        }

        return (string) $name;
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

        $keys = collect($ids)
            ->filter(fn (mixed $id): bool => is_int($id) || is_string($id))
            ->values();

        return $relation->getQuery()
            ->whereKey($keys)
            ->get()
            ->mapWithKeys(fn (Model $record): array => self::nameEntry($record))
            ->all();
    }

    /**
     * @return array<array-key, string>
     */
    private static function nameEntry(Model $record): array
    {
        $key = $record->getKey();
        $name = self::recordName($record);

        if (! is_int($key) && ! is_string($key)) {
            return [];
        }

        if ($name === null) {
            return [];
        }

        return [$key => $name];
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

        if (! $relation instanceof BelongsTo || $relation->getForeignKeyName() !== $field) {
            return null;
        }

        /** @var BelongsTo<Model, Model> $relation */
        return $relation;
    }

    /**
     * @param  array<array-key, string>|null  $names
     */
    private static function format(?Model $model, string $field, mixed $value, ?array $names): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => (string) __($value ? 'common/audit.yes' : 'common/audit.no'),
            $names !== null && (is_int($value) || is_string($value)) => $names[(string) $value] ?? '#'.$value,
            is_string($value) && self::isDate($model, $field) => (string) Preferences::current()->calendarDate(CarbonImmutable::parse($value)),
            is_string($value) && self::isDateTime($model, $field) => (string) Preferences::current()->datetime(CarbonImmutable::parse($value)),
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };
    }

    private static function isDate(?Model $model, string $field): bool
    {
        return (bool) $model?->hasCast($field, self::DATE_CASTS);
    }

    private static function isDateTime(?Model $model, string $field): bool
    {
        if (! $model instanceof Model) {
            return false;
        }

        return $model->hasCast($field, self::DATETIME_CASTS) || collect($model->getDates())->containsStrict($field);
    }
}
