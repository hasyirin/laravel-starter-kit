# Act

`<x-act>` shows who did an action on a record, such as who prepared it: the name of the user, the position that the user held then, and the time. The kit records actions with [laravel-actor](https://github.com/hasyirin/laravel-actor).

```blade
<x-act name="prepare">Not prepared yet.</x-act>
```

`name` takes an `Act` case or its value. While the record has no such action, the act shows its slot, for example a note or a field that picks who acts next.

## Action

`action` takes an action of a record. The act takes its name from the action, so it needs `name` only for the time before the action exists.

```html
<x-act :action="$invoice->preparer" />

<x-act name="prepare" :action="$invoice->action(App\Domain\Shared\Actor\Enums\Act::Prepare)">Not prepared yet.</x-act>
```

Each action keeps the profile that its user had when they acted. So the act shows the position of that day, also after the user moves to another position, and it still shows the name of a user who was deleted later. A user without a profile gets their name and no position. The time follows the time zone and the locale of the user who reads the page.

## Record an Action

Give the model the `Hasyirin\Actor\Concerns\InteractsWithActions` trait. `act()` records an action of the signed-in user, or of the user that you pass. A record keeps one action of each name, so acting again replaces the user and the time.

```php
use App\Domain\Shared\Actor\Enums\Act;
use Hasyirin\Actor\Concerns\InteractsWithActions;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    use InteractsWithActions;
}

$invoice->act(Act::Prepare);
$invoice->act(Act::Prepare, $user, new Carbon($row->prepared_at));
```

Make the time with `new Carbon()`. `act()` accepts only an `Illuminate\Support\Carbon`, but `now()` gives a `Carbon\CarbonImmutable` in the kit, and Rector turns `Illuminate\Support\Carbon::parse()` into `Date::parse()`, which does too.

Add a case to `App\Domain\Shared\Actor\Enums\Act` for each kind of action, and its text to `lang/en/common/acts.php`: `label` names the action, and `by` names the person, such as "Prepared by".

## Preparer

`InteractsWithPreparer` records the signed-in user as the preparer when a record is created, and `preparer` gives that action. A record that is created without a signed-in user, such as in a seeder or a queued job, gets no preparer.

```php
use App\Domain\Shared\Actor\Concerns\InteractsWithPreparer;

class Invoice extends Model
{
    use InteractsWithPreparer;
}
```

## Reference

### Props

| Prop     | Default | Description                                                         |
|----------|---------|---------------------------------------------------------------------|
| `name`   |         | The `Act` case, or its value. Without it, the act takes the name of `action`. |
| `action` |         | The action to show. Without it, the act shows its slot.             |

The act renders nothing when it has neither a `name` nor an `action`.

### Slots

| Slot    | Description                       |
|---------|-----------------------------------|
| Default | Shown while `action` is `null`.   |

### Attributes

All other attributes go to the list, such as `class` and `wire:key`.
