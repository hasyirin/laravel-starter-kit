<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Shared\Common\Exceptions\StateNotSaved;
use App\Domain\Shared\Common\States\State;
use App\Livewire\Concerns\InteractsWithAbilities;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;
use Spatie\ModelStates\Exceptions\TransitionNotAllowed;

abstract class Component extends \Livewire\Component
{
    use InteractsWithAbilities;

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

        try {
            $state->transitionTo($to, ...$arguments);
        } catch (TransitionNotAllowed) {
            Flux::toast(variant: 'warning', text: $state->reasonFor($to, ...$arguments) ?? __('common/workflows.changed'));

            return false;
        } catch (StateNotSaved) {
            Flux::toast(variant: 'danger', text: __('common/workflows.not_saved'));

            return false;
        } catch (CouldNotPerformTransition) {
            $this->reloadChangedRecord();

            return false;
        }

        return true;
    }

    private function reloadChangedRecord(): void
    {
        Flux::toast(variant: 'warning', text: __('common/workflows.changed'));

        $this->redirect(url()->previous(), navigate: true);
    }
}
