import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import card from './components/card';
import nameEditors from './components/editor';
import blurOnLeave from './components/field';
import inputField from './components/input';
import datetimeField from './components/input/datetime';
import numberField from './components/input/number';
import map from './components/map';
import passkeyModal from './components/passkey-modal';
import permissionTree from './components/permission-tree';
import repeatableRows from './components/repeatable-rows';
import sliderField from './components/slider';
import tabs from './components/tabs';

const remember = (name, value) => {
    document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=31536000; samesite=lax${location.protocol === 'https:' ? '; secure' : ''}`;
};

const reloadForBrowserPreferences = () => {
    if (!document.querySelector('meta[name="browser-preferences"]')) {
        return false;
    }

    // If the browser blocks cookies, the page has the mark again: this key stops a second reload.
    try {
        if (sessionStorage.getItem('browser-preferences-reloaded')) {
            return false;
        }

        sessionStorage.setItem('browser-preferences-reloaded', '1');
    } catch {
        return false;
    }

    window.location.reload();

    return true;
};

const start = () => {
    window.livewireStarted = true;
    // The server fills an empty time zone, language, and locale of the user from these.
    remember('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone);
    remember('locale', navigator.languages.join(','));

    if (reloadForBrowserPreferences()) {
        return;
    }

    blurOnLeave();
    nameEditors();
    Alpine.data('card', card);
    Alpine.data('inputField', inputField);
    Alpine.data('datetimeField', datetimeField);
    Alpine.data('numberField', numberField);
    Alpine.data('map', map);
    Alpine.data('passkeyModal', passkeyModal);
    Alpine.data('permissionTree', permissionTree);
    Alpine.data('repeatableRows', repeatableRows);
    Alpine.data('sliderField', sliderField);
    Alpine.data('tabs', tabs);
    Livewire.start();
};

// After a deploy, wire:navigate runs the new build of this file: reload instead of starting a second Livewire.
if (window.livewireStarted) {
    window.location.reload();
} else {
    start();
}
