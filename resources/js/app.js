import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import inputField from './components/input';

// After a deploy, wire:navigate runs the new build of this file: reload instead of starting a second Livewire.
if (window.livewireStarted) {
    window.location.reload();
} else {
    window.livewireStarted = true;
    Alpine.data('inputField', inputField);
    Livewire.start();
}
