import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import inputField from './components/input';
import numberField from './components/input/number';

// After a deploy, wire:navigate runs the new build of this file: reload instead of starting a second Livewire.
if (window.livewireStarted) {
    window.location.reload();
} else {
    window.livewireStarted = true;
    Alpine.data('inputField', inputField);
    Alpine.data('numberField', numberField);
    Livewire.start();
}
