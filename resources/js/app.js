import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

// After a deploy, wire:navigate runs the new build of this file: reload instead of starting a second Livewire.
if (window.livewireStarted) {
    window.location.reload();
} else {
    window.livewireStarted = true;
    Livewire.start();
}
