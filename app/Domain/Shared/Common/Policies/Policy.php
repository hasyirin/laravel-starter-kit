<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Policies;

use App\Domain\Shared\Common\States\State;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class Policy
{
    protected static string $root = 'system.permissions';

    protected static string $prefix = '';

    /**
     * @var class-string|null
     */
    protected static ?string $model = null;

    public function path(string $action): string
    {
        return collect([
            static::$root,
            static::$prefix,
            str(static::$model ?? str(static::class)->classBasename()->remove('Policy')->value())->classBasename()->kebab()->plural()->value(),
            $action,
        ])->filter()
            ->join('.');
    }

    public function transition(User $user, Model $model, string $field, string $state): bool
    {
        $current = $model->getAttribute($field);

        return $current instanceof State && $current->hasStepTo($state) && $this->hasPermission($user, $current::abilityTo($current::getMorphClass(), $state));
    }

    protected function hasPermission(User $user, string $action): bool
    {
        return $user->hasPermissionTo(config()->string($this->path($action)));
    }

    /**
     * @param  string|array<array-key, string>|Arrayable<array-key, string>  ...$actions
     */
    protected function hasAnyPermission(User $user, string|array|Arrayable ...$actions): bool
    {
        return $this->actions($actions)->contains(fn (string $action): bool => $this->hasPermission($user, $action));
    }

    /**
     * @param  string|array<array-key, string>|Arrayable<array-key, string>  ...$actions
     */
    protected function hasAllPermissions(User $user, string|array|Arrayable ...$actions): bool
    {
        return $this->actions($actions)->every(fn (string $action): bool => $this->hasPermission($user, $action));
    }

    /**
     * @param  array<array-key, string|array<array-key, string>|Arrayable<array-key, string>>  $actions
     * @return Collection<int, string>
     */
    private function actions(array $actions): Collection
    {
        /** @var Collection<int, string> $flattened */
        $flattened = collect($actions)->flatMap(fn (string|array|Arrayable $action): array => $action instanceof Arrayable ? $action->toArray() : (array) $action)->values();

        return $flattened;
    }
}
