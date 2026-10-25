<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Support;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\Shared\Common\States\State;
use App\Domain\Shared\Permission\Models\Role;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class StateGraph
{
    /**
     * @var Collection<int, Role>|null
     */
    private ?Collection $roles = null;

    /**
     * @param  class-string<Model>  $model
     * @param  class-string<State<Model>>  $state
     */
    private function __construct(private readonly string $model, private readonly string $state) {}

    /**
     * @param  array<string, string>  $options
     */
    public static function fromOptions(array $options): self
    {
        $model = $options['model'] ?? '';
        $field = $options['field'] ?? '';
        $state = is_subclass_of($model, Model::class) ? new $model()->getCasts()[$field] ?? null : null;

        if (is_string($state) && is_subclass_of($state, State::class)) {
            return new self($model, $state);
        }

        throw new InvalidArgumentException("A state section needs a model and its state field, but [{$model}] and [{$field}] are not.");
    }

    /**
     * @return array<string, string>
     */
    public function states(): array
    {
        return $this->state::options()
            ->map(fn (Htmlable|string $label): string => $label instanceof Htmlable ? $label->toHtml() : $label)
            ->all();
    }

    /**
     * @return list<array{from: string, to: string, step: string}>
     */
    public function transitions(): array
    {
        return array_map(fn (string $key): array => [
            'from' => str($key)->before('->')->value(),
            'to' => str($key)->after('->')->value(),
            'step' => $this->state::abilityTo(str($key)->before('->')->value(), str($key)->after('->')->value()),
        ], array_keys($this->state::config()->allowedTransitions));
    }

    public function default(): ?string
    {
        $default = $this->state::config()->defaultStateClass;

        return $default === null ? null : $default::getMorphClass();
    }

    public function permission(string $step): ?string
    {
        $policy = Gate::getPolicyFor($this->model);
        $permission = $policy instanceof Policy ? config($policy->path($step)) : null;

        return is_string($permission) ? $permission : null;
    }

    /**
     * @return array<int, string>
     */
    public function roles(string $permission): array
    {
        return $this->allRoles()
            ->reject(fn (Role $role): bool => $this->bypasses($role))
            ->filter(fn (Role $role): bool => $role->permissions->contains('name', $permission))
            ->map(fn (Role $role): string => $role->label)
            ->values()
            ->all();
    }

    public function bypassNote(): ?string
    {
        $bypass = config()->string('system.permissions.bypass-authorization');

        return $this->allRoles()->contains(fn (Role $role): bool => $this->bypasses($role)) ? "A role with `{$bypass}` passes every check." : null;
    }

    private function bypasses(Role $role): bool
    {
        return $role->permissions->contains('name', config()->string('system.permissions.bypass-authorization'));
    }

    /**
     * @return Collection<int, Role>
     */
    private function allRoles(): Collection
    {
        return $this->roles ??= Role::query()->with('permissions')->orderBy('name')->get();
    }
}
