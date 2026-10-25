<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\States;

use App\Domain\Shared\Common\Concerns\Enums\InteractsWithColor;
use App\Domain\Shared\Common\Concerns\Enums\InteractsWithLabel;
use App\Domain\Shared\Common\Contracts\Enums\HasColor;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;
use App\Domain\Shared\Common\Events\StateChanged;
use App\Domain\Shared\Common\Transitions\Transition;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\ModelStates\DefaultTransition;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;
use Spatie\ModelStates\StateConfig;

/**
 * @template TModel of Model
 *
 * @extends \Spatie\ModelStates\State<TModel>
 */
abstract class State extends \Spatie\ModelStates\State implements HasColor, HasLabel
{
    use InteractsWithColor;
    use InteractsWithLabel;

    public static function config(): StateConfig
    {
        return parent::config()->stateChangedEvent(StateChanged::class);
    }

    /**
     * @return Collection<string, Htmlable|string>
     */
    public static function options(): Collection
    {
        return collect(static::getStateMapping()->all())
            ->mapWithKeys(fn (mixed $state, mixed $name): array => [(string) $name => new (static::stateClass((string) $state))(null)->label()]);
    }

    public static function abilityTo(string $from, string $to): string
    {
        $to = static::stateClass($to);
        $transition = static::config()->resolveTransitionClass(static::stateClass($from)::getMorphClass(), $to::getMorphClass());

        return $transition !== null && is_subclass_of($transition, Transition::class)
            ? $transition::ability()
            : str(class_basename($to))->kebab()->value();
    }

    /**
     * @return class-string<State<Model>>
     */
    protected static function stateClass(string $state): string
    {
        $class = static::resolveStateClass($state);

        if ($class !== null && is_subclass_of($class, self::class)) {
            return $class;
        }

        throw new InvalidArgumentException("[{$state}] is not a state of ".static::class.'.');
    }

    public function label(): string
    {
        return str(class_basename(static::class))->headline()->value();
    }

    public function color(): string
    {
        return 'gray';
    }

    /**
     * @param  \Spatie\ModelStates\State<Model>|string  ...$states
     */
    public function is(\Spatie\ModelStates\State|string ...$states): bool
    {
        return $this->equals(...$states);
    }

    /**
     * @param  \Spatie\ModelStates\State<Model>|string  ...$states
     */
    public function not(\Spatie\ModelStates\State|string ...$states): bool
    {
        return ! $this->equals(...$states);
    }

    /**
     * @return list<\Spatie\ModelStates\State<Model>>
     */
    public function allowedFor(User $user): array
    {
        return array_values(array_filter(
            $this->transitionableStateInstances(),
            fn (mixed $state): bool => $state instanceof \Spatie\ModelStates\State && $user->can('transition', [$this->getModel(), $this->getField(), $state::class]),
        ));
    }

    public function reasonFor(string $state, mixed ...$arguments): ?string
    {
        $from = static::getMorphClass();
        $to = static::stateClass($state);

        if (! static::config()->isTransitionAllowed($from, $to::getMorphClass())) {
            throw CouldNotPerformTransition::notFound($from, $to::getMorphClass(), $this->getModel());
        }

        $class = static::config()->resolveTransitionClass($from, $to::getMorphClass()) ?? config()->string('model-states.default_transition');

        $transition = is_a($class, DefaultTransition::class, true)
            ? new $class($this->getModel(), $this->getField(), new $to($this->getModel()), ...$arguments)
            : new $class($this->getModel(), ...$arguments);

        return $transition instanceof Transition ? $transition->reason() : null;
    }
}
