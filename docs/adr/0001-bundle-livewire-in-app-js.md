# Bundle Livewire and Alpine in app.js

`resources/js/app.js` imports Livewire's ESM build, registers the kit's Alpine components, and calls `Livewire.start()`. Every layout loads this bundle with `@livewireScriptConfig`, so Livewire does not inject its own script. We chose this because the kit, and each project built from it, registers Alpine components and plugins there before Livewire starts.

## Consequences

- Load the bundle in every new layout the same way, with `@livewireScriptConfig`. `@livewireScripts` next to the bundle would start a second copy of Livewire.
- After a Livewire update, rebuild the assets, because the bundle contains Livewire's JavaScript.
- A deploy gives app.js a new hashed name, and `wire:navigate` runs the new file in open tabs. So app.js reloads the page instead of starting a second Livewire.
