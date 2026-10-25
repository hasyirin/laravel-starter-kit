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
    public const string ANY = 'any_state';

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

    public static function quote(string $text): string
    {
        return str_replace('"', '#quot;', $text);
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

    /**
     * @return list<array{from: string|null, to: string, step: string}>
     */
    public function steps(): array
    {
        $transitions = $this->transitions();
        $others = count($this->states()) - 1;
        $open = [];

        foreach (array_unique(array_column($transitions, 'to')) as $to) {
            $into = array_filter($transitions, fn (array $transition): bool => $transition['to'] === $to);
            $sources = array_unique(array_column(array_filter($into, fn (array $transition): bool => $transition['from'] !== $to), 'from'));

            $open[$to] = $others >= 3 && count($sources) === $others && count(array_unique(array_column($into, 'step'))) === 1;
        }

        $steps = [];

        foreach ($transitions as $transition) {
            $collapsed = ['from' => null, 'to' => $transition['to'], 'step' => $transition['step']];

            if (! $open[$transition['to']]) {
                $steps[] = $transition;
            } elseif (! in_array($collapsed, $steps, true)) {
                $steps[] = $collapsed;
            }
        }

        return $steps;
    }

    public function default(): ?string
    {
        $default = $this->state::config()->defaultStateClass;

        return $default === null ? null : $default::getMorphClass();
    }

    public function hasPolicy(): bool
    {
        return Gate::getPolicyFor($this->model) instanceof Policy;
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
