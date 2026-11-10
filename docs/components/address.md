# Address

`<x-address>` is the input for a Malaysian address: three lines, the postcode, the city, and the state. A form object, `AddressForm`, holds the values and the rules, and `SaveAddress` writes them to a model. The user picks the postcode from a searchable list, and the form fills in the city and the state.

```blade
<livewire:pages.local.examples.address />
```

The examples show real places only after `php artisan db:seed`, because `address:seed` fills the place tables.

## Use It on a Page

Give the page an `AddressForm` property, and render it with `<x-address :form="$address" />`. An edit page passes its model to `set()`, and `save()` writes the address to that model:

```php
#[Locked]
public Organization $organization;

public AddressForm $address;

public function mount(): void
{
    $this->address->required()->set($this->organization);
}

public function save(): void
{
    $this->address->save();
    $this->address->set($this->organization);
}
```

A create page has no model when it opens. It passes the new model to `save()` instead, after its main form saves it. Call `$this->validate()` before the saves. It validates all forms of the component together, so the user sees all errors at once. A bad address also stops the save before the new record exists:

```php
public function save(): void
{
    $this->validate();

    $organization = $this->form->save();
    $this->address->save($organization);
}
```

Pass the model once. When `set()` got it, call `save()` without it. The Forms section of `docs/livewire.md` gives this rule for every form of a child record.

`save()` returns the saved `Address`. When the user leaves an optional address empty, it returns null and deletes the saved address of that type.

## Required

`required()` makes the address required. Line 1, the postcode, and the city then show a required mark.

```blade
<livewire:pages.local.examples.address required />
```

An optional address can stay empty. When the user fills any part of it, line 1, the postcode, and the city are required too. So the form never saves half an address.

## Label

`label` replaces the label above line 1.

```blade
<livewire:pages.local.examples.address label="Home address" />
```

## Disabled

`<x-address>` follows `disabled` on the card or card group around it, like every kit input. Its own `disabled` attribute replaces the card's.

```blade
<livewire:pages.local.examples.address disabled />
```

## Postcode

The Postcode field is a select with a search box. The server finds the options, as the "Search on the Server" section of [Select](select.md) describes. The list stays empty until the search has two characters. Then it shows up to 20 postcodes that start with the search text, and it always keeps the picked postcode. With `local()`, the list shows the local postcodes at once, and the search narrows them.

### Postcode Without an Address

`Postcode::options()` builds this list, so another page can show the same field without an address. Give the page a search property, and pass the search text and the picked postcode:

```php
public string $postcodeSearch = '';

/**
 * @return Collection<string, string>
 */
#[Computed]
public function postcodes(): Collection
{
    return Postcode::options($this->postcodeSearch, current: $this->form->postcode);
}
```

```html
<x-select :label="__('common/inputs.postcode')" :options="$this->postcodes" search-model="postcodeSearch" search:inputmode="numeric" wire:model.live="form.postcode" />
```

Pass `local: true` to list only the local postcodes. Validate the postcode in the page's own rules, for example with `Rule::exists(Postcode::class, 'code')->withoutTrashed()`. For a local list, also add `App\Domain\Shared\Geography\Rules\LocalPostcode`. It fails a postcode outside the local area with the same error as the address form.

## The City Is the Post Office

In a Malaysian address, the name after the postcode is the post office that delivers the mail. It is not the town or the council. So the City field stores a post office: the form saves `post_office_id`, and the model is `PostOffice`.

A postcode usually has one post office, and the form selects it. A few postcodes have two, such as 84300. Then the user picks one.

The address takes its state and its country from the post office, not from the browser. A post office can serve houses across a state border. Such an address gets the state of the post office.

## Districts

The district fields are hidden by default. `withDistricts()` shows District and Subdistrict as optional fields, and `withDistricts(required: true)` makes them required.

```blade
<livewire:pages.local.examples.address districts="optional" />
```

```blade
<livewire:pages.local.examples.address required districts="required" />
```

- The District list has the districts of the city's state, so it fills after the user picks the city. When the state has one district, the form selects it.
- Subdistrict shows only when the district has subdistricts. The bundled data has them only for Negeri Sembilan and Kemaman. A required subdistrict is required only when the district has subdistricts.
- A city in another state clears the district and the subdistrict. A new district clears the subdistrict.
- While the fields are hidden, the form leaves the saved district as it is while the city stays the same. If the city changes, `SaveAddress` clears the saved district and subdistrict, because only the state can check a district against the city. A page with the district fields then asks for them again.

## Local Area

`local()` accepts only addresses in the app's local area. The Postcode list then shows only local postcodes, without a search, and the City list only local post offices. A postcode or a city outside the area fails with its own error. Set the codes of the local country, state, and district in `config/address.php`:

```php
'locality' => [
    'country' => 'MYS',
    'state' => '11',
    'district' => '03',
],
```

```php
$this->address->required()->local()->set($this->holding);
```

By default, the local area is every post office of the local state. The `local` scope of `Hasyirin\Address\Models\PostOffice` finds them. For a smaller area, override `scopeLocal()` in `app/Domain/Shared/Geography/Models/PostOffice.php`. Call the parent, then add names. Use names, not ids, because ids differ between databases:

```php
/**
 * @param  Builder<\Hasyirin\Address\Models\PostOffice>  $query
 */
public function scopeLocal(Builder $query): void
{
    parent::scopeLocal($query);

    $query->whereIn('name', ['Pasir Gudang', 'Masai', 'Ulu Tiram']);
}
```

Read the area with `PostOffice::query()->local()`. On `State` and `District`, the static `local()` returns one model instead.

`local()` fails at once when `address.locality.state` is not set. When the config also sets a district, `local()` with `withDistricts()` preselects that district. The user then picks only the subdistrict.

## Location

`withLocation()` shows a [map](map.md) below the address, with Latitude and Longitude inputs. The form saves them to `latitude` and `longitude` of the address. `withLocation(required: true)` makes the location required.

```blade
<livewire:pages.local.examples.address location="optional" />
```

- Both values are needed, or neither. The latitude must be between -90 and 90, and the longitude between -180 and 180.
- The form rounds both values to 6 decimals.
- A location counts as a filled part. So a location alone makes line 1, the postcode, and the city required.
- A required location is required only when the address is required or filled, like the districts.
- `copy()` always fills the location. `values()` includes it only when the form shows it.
- While the map is hidden, `SaveAddress` keeps the saved location only if the lines, the postcode, and the city stay the same. If any of them changes, it clears the location, because nothing can check the old location against the new address. A page with the map then asks for it again.

Attributes that start with `location:` go to the map without the prefix. Bind a boolean with a colon, so it is not the string "false":

```html
<x-address :form="$address" :location:clickable="false" />
```

## Address Type

A model can have one address of each type. The default type is primary. For another type, call `type()` before `set()`:

```php
$this->mailing->type('mailing')->set($this->organization);
```

## Copy an Address

`copy()` fills the form from a saved address of another model, such as the owner's. The form still saves to its own model:

```php
$this->address->copy($owner->address);
```

## Same as Above

A second address is often the same as the first, such as a mailing address and a home address. Give the page a "Same as above" checkbox. While the box is ticked, hide the second address, and copy the first address into it when the page saves. The copy is saved as its own address, so code that reads the mailing address needs no fallback.

```php
public bool $sameAsHome = false;

public function mount(): void
{
    $this->home->required()->set($this->organization);
    $this->mailing->type('mailing')->set($this->organization);
    $this->sameAsHome = $this->mailing->values() === $this->home->values();
}

public function save(): void
{
    if ($this->sameAsHome) {
        $this->mailing->fill($this->home->values());
    }

    $this->validate();

    $this->home->save();
    $this->mailing->save();
}
```

`$this->validate()` checks both addresses before either one saves. So a bad mailing address does not leave only the home address saved.

The page ticks the box when the two saved addresses are the same, so it needs no extra column. A new record starts with the box ticked, because both addresses are empty.

Show the second address when the box is not ticked. Also show it when the copy fails a rule of the second form. For example, a mailing form with `local()` rejects a home address outside the local area:

```html
<x-address :form="$home" />

<x-checkbox wire:model.live="sameAsHome" label="Same as above" />

@if(! $sameAsHome || $errors->has('mailing.*'))
    <x-address :form="$mailing" label="Mailing address" />
@endif
```

## Give a Model Addresses

A model with addresses implements `Hasyirin\Address\Contracts\Addressable` and uses `Hasyirin\Address\Concerns\InteractsWithAddresses`. Give it a morph alias in `EloquentServiceProvider`, because the address stores the alias.

```php
class Organization extends Model implements Addressable
{
    use InteractsWithAddresses;
}
```

`$organization->address` is the primary address. It is never null: without a saved address, it is an empty one. `$organization->address('mailing')` gives the relation of another type.

## Show an Address

`Address::lines()` gives the lines to print, one per line, and `formatted()` joins them into one string. Both read the post office, the state, and the country. So eager load them for a list, or strict mode throws on the lazy load:

```php
$organizations = Organization::query()->with(['address.postOffice', 'address.state', 'address.country'])->get();
```

## Validation

- Line 1, the postcode, and the city are required when the address is required or when any part is filled.
- The postcode must exist, and it must belong to the selected city.
- A district must be in the state of the city, and a subdistrict must be in the district.
- With `local()`, the postcode and the city must be in the local area.
- Soft-deleted places are not listed and not accepted.

## Reference

### Props

| Prop    | Default   | Description                         |
|---------|-----------|-------------------------------------|
| `form`  |           | The `AddressForm` to show. Required. |
| `label` | "Address" | The label above line 1.             |

### Attributes

`disabled` disables every field. Without it, the address follows the card around it. Attributes that start with `location:` go to the map without the prefix. `class` goes to the wrapper.

### Form Methods

| Method                          | Description                                                                                         |
|---------------------------------|-----------------------------------------------------------------------------------------------------|
| `AddressForm::required()`       | Makes line 1, the postcode, and the city required.                                                  |
| `AddressForm::local()`          | Accepts only addresses in the local area.                                                           |
| `AddressForm::withDistricts()`  | Shows District and Subdistrict. With `required: true`, they are required.                           |
| `AddressForm::withLocation()`   | Shows the map with Latitude and Longitude. With `required: true`, the location is required.         |
| `AddressForm::type()`           | Sets the address type. Call it before `set()`.                                                      |
| `AddressForm::set()`            | Keeps the model and fills the form from its saved address.                                          |
| `AddressForm::copy()`           | Fills the form from another saved address, and keeps the model.                                     |
| `AddressForm::save()`           | Validates, then saves to the model from `set()`, or to the model in the argument.                   |
| `AddressForm::values()`         | The values that `SaveAddress` saves, also for copying a form.                                       |
| `AddressForm::postcodes()`      | The postcodes that match the search text, plus the picked one. Only local ones with `local()`.      |
| `AddressForm::postOffices()`    | The cities of the postcode.                                                                         |
| `AddressForm::districts()`      | The districts of the city's state.                                                                  |
| `AddressForm::subdistricts()`   | The subdistricts of the selected district.                                                          |
