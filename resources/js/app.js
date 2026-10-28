import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import nameEditors from './components/editor';
import blurOnLeave from './components/field';
import inputField from './components/input';
import datetimeField from './components/input/datetime';
import numberField from './components/input/number';
import sliderField from './components/slider';

const remember = (name, value) => {
    document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=31536000; samesite=lax${location.protocol === 'https:' ? '; secure' : ''}`;
};

// After a deploy, wire:navigate runs the new build of this file: reload instead of starting a second Livewire.
if (window.livewireStarted) {
    window.location.reload();
} else {
    window.livewireStarted = true;
    // The server fills an empty time zone and locale of the user from these.
    remember('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone);
    remember('locale', navigator.languages.join(','));
    blurOnLeave();
    nameEditors();
    Alpine.data('inputField', inputField);
    Alpine.data('datetimeField', datetimeField);
    Alpine.data('numberField', numberField);
    Alpine.data('sliderField', sliderField);
    Livewire.start();
}
