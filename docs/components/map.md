# Map

`<x-map>` shows a location on a Leaflet map, with a Latitude and a Longitude input below it. The user clicks the map or drags the marker to set the location, or types the values. Each property gets a number with up to 6 decimals.

```blade
<x-map latitude-model="form.latitude" longitude-model="form.longitude" />
```

The map loads Leaflet only on a page that shows a map. It keeps its size when the window changes size, and when a folded card or a hidden tab around it opens.

## Label and Required

`label` replaces "Location" above the map. `required` shows a required mark on the Latitude and Longitude labels. It does not validate. The form's rules do that.

```blade
<x-map label="Site" latitude-model="form.plot_latitude" longitude-model="form.plot_longitude" required />
```

## Map and Inputs

Two switches control what the user can change:

- `clickable` lets a click on the map or a drag of the marker set the location.
- `editable-inputs` lets the user type the latitude and the longitude. When it is off, the inputs are read-only.

Both are on by default. A Livewire update that changes them, or `disabled`, applies at once. Turn off `clickable` when the user must type the values. The marker then follows the inputs:

```blade
<x-map latitude-model="form.site_latitude" longitude-model="form.site_longitude" :clickable="false" />
```

Turn off `editable-inputs` when the user must pick the location on the map:

```blade
<x-map latitude-model="form.office_latitude" longitude-model="form.office_longitude" :editable-inputs="false" />
```

## Disabled

`<x-map>` follows `disabled` on the card or card group around it, like every kit input. Its own `disabled` attribute replaces the card's. A disabled map turns off both switches. The user can still move the map and zoom it.

```blade
<x-card disabled>
    <x-map latitude-model="form.depot_latitude" longitude-model="form.depot_longitude" />
</x-card>
```

## Show a Saved Location

Without the models, the map shows the `latitude` and `longitude` values and the user cannot change them. Use it on a page that only shows a record:

```blade
<x-map :latitude="1.4927" :longitude="103.7414" />
```

## In a Folded Card

The map measures itself again when the card opens. A saved location is then in the middle of the map.

```blade
<x-card heading="Branch" accordion collapsed>
    <x-map latitude-model="form.branch_latitude" longitude-model="form.branch_longitude" />
</x-card>
```

## Layers and the First View

`config/map.php` sets the tile layers, and the first one shows by default. The map fails at once when the config has no layer. When the config has more than one layer, the map shows a control to switch between them. The kit lists OpenStreetMap, Google Street, and Google Satellite. Each layer has a `label` translation key, a `url`, an `attribution`, a `max_zoom`, and optional `subdomains`.

The Google layers use Google's public tile URLs. They need no key, but Google's terms do not license them. Remove them from the config if your app must use only licensed tiles.

Without a location, the map shows `map.center` at `map.zoom`, by default the whole of Malaysia. With a location, it zooms to `map.point_zoom`.

## Validation

The map does not validate. `AddressForm` has its own rules, so a page with an address needs none. On another page, type both properties as `?float`, because Livewire then turns an empty input into null. Then add these rules, so that the location has both values or neither, and each value is on the earth:

```php
'latitude' => [Rule::requiredIf($this->longitude !== null), 'nullable', 'numeric', 'between:-90,90'],
'longitude' => [Rule::requiredIf($this->latitude !== null), 'nullable', 'numeric', 'between:-180,180'],
```

Keep `numeric`. Without it, `between` checks the length of the text, not the value.

For a required location, use `'required'` in place of `Rule::requiredIf(...)` and `'nullable'`. Store the values in `decimal(10, 6)` columns. The map and the inputs give 6 decimals.

## In an Address

`AddressForm::withLocation()` adds the map to `<x-address>`. See the Location section of [Address](address.md).

## Reference

### Props

| Prop              | Default    | Description                                                                         |
|-------------------|------------|-------------------------------------------------------------------------------------|
| `latitude-model`  |            | The property of the latitude. Without both models, the user cannot change the map. |
| `longitude-model` |            | The property of the longitude.                                                      |
| `latitude`        |            | The latitude to show when the map has no models.                                    |
| `longitude`       |            | The longitude to show when the map has no models.                                   |
| `clickable`       | `true`     | Lets a click on the map or a drag of the marker set the location.                   |
| `editable-inputs` | `true`     | Lets the user type the latitude and the longitude.                                  |
| `label`           | "Location" | The label above the map.                                                            |
| `required`        | `false`    | Shows a required mark on the Latitude and Longitude labels.                        |

### Attributes

`disabled` disables the map and the inputs. Without it, the map follows the card around it. `class` and all other attributes go to the wrapper.
