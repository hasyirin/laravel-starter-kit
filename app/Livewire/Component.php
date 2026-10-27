<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Shared\Common\Exceptions\StateNotSaved;
use App\Domain\Shared\Common\Exceptions\TransitionBlocked;
use App\Domain\Shared\Common\States\State;
use App\Livewire\Concerns\InteractsWithAbilities;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;
use Throwable;

abstract class Component extends \Livewire\Component
{
    use InteractsWithAbilities;

    /**
     * @param  callable(): void  $stopPropagation
     */
    public function exception(Throwable $e, callable $stopPropagation): void
    {
        if (! $e instanceof CouldNotPerformTransition) {
            return;
        }

        match (true) {
            $e instanceof TransitionBlocked => Flux::toast(variant: 'warning', text: $e->reason),
            $e instanceof StateNotSaved => Flux::toast(variant: 'danger', text: __('common/workflows.not_saved')),
            default => $this->reloadChangedRecord(),
        };

        $stopPropagation();
    }

    /**
     * @template TModel of Model
     *
     * @param  State<TModel>  $state
     */
    protected function transitionTo(State $state, string $to, mixed ...$arguments): bool
    {
        if (! $state->hasStepTo($to)) {
            $this->reloadChangedRecord();

            return false;
        }

        $this->authorize('transition', [$state->getModel(), $state->getField(), $to]);

        $state->transitionTo($to, ...$arguments);

        return true;
    }

    private function reloadChangedRecord(): void
    {
        Flux::toast(variant: 'warning', text: __('common/workflows.changed'));

        $this->redirect(url()->previous(), navigate: true);
    }
}
