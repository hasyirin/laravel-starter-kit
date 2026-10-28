# Tabs

A tab set shows a bar of tabs and the content of the current tab. A click on a tab swaps only the content. The page does not load again, and at first only the current tab mounts its content.

```blade
<livewire:pages.local.examples.tabs query-as="intro" />
```

A tab set lives in a Livewire component that uses the `InteractsWithTabs` trait. The component lists its tabs in `tabs()`, and its view puts a bar and `<x-tabs.content>` where they belong:

```php
use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;

class ProjectTabs extends Component
{
    use InteractsWithTabs;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('overview')->label((string) __('projects.tabs.overview'))->content(Overview::class),
            Tab::make('members')->label((string) __('projects.tabs.members'))->content(Members::class)->keepAlive(),
        ];
    }
}
```

```html
<div>
    <x-tabs :$tabs :$currentTab :query-as="$queryTabAs" />

    <x-tabs.content :panels="$tabPanels" :$currentTab class="mt-4" />
</div>
```

The trait gives the view these variables:

- `$tabs`: the visible tabs, for the bar.
- `$currentTab`: the current tab.
- `$tabPanels`: the tabs whose content renders now, for `<x-tabs.content>`.
- `$queryTabAs`: the URL key of the tab set, for the bar.

## How a Switch Works

Each tab is a link. A plain click calls `InteractsWithTabs::switchTab()` on the component that owns the tab set, so only that component renders again. The bar marks the clicked tab at once, and the current content dims until the server answers. A kept-alive tab that already rendered shows at once. When the user clicks again before the server answers, the last click wins. When a request fails, the bar marks the current tab again. A middle click, a click with Ctrl or Cmd, and "open in new tab" follow the link.

`InteractsWithTabs::switchTab()` ignores a tab that is unknown, hidden, or disabled. When a tab changes in another way, for example with the Back button, the trait checks it in the same way and keeps the tab it had.

## The URL

The tab set keeps its tab in the URL, under the key from `queryTabAs()`. The default is `tab`. A switch changes only this key and keeps all other query parameters, such as the search of a table. Each switch adds a step to the browser history, so Back opens the tab before.

The default tab is the first tab that is visible and not disabled. Its key stays out of the URL, so the page keeps a clean URL. For the default tab, the `$tab` property holds an empty string. To read the current tab in code, use `$this->currentTab`. It gives the `Tab`, also for the default tab.

Give a tab set another key when the page has more than one set, or when `tab` already means something on the page:

```php
public function queryTabAs(): string
{
    return 'section';
}
```

When the URL holds a tab that is unknown, hidden, or disabled, the tab set opens the default tab. When no tab is available, the page answers 404.

## Bars

`<x-tabs>` renders a [Flux tabs](https://fluxui.dev/components/tabs) bar. It keeps the Flux default look, the underline. `variant` goes to Flux, so `segmented` and `pills` work too.

```blade
<livewire:pages.local.examples.tabs query-as="segmented" variant="segmented" />
```

```blade
<livewire:pages.local.examples.tabs query-as="pills" variant="pills" />
```

`<x-tabs.navbar>` renders the tabs as a [Flux navbar](https://fluxui.dev/components/navbar).

```blade
<livewire:pages.local.examples.tabs query-as="navbar" bar="navbar" />
```

`<x-tabs.navlist>` renders the tabs as a vertical [Flux navlist](https://fluxui.dev/components/navlist). Put the bar and the content side by side, and give the content `orientation="vertical"`, so it slides in from the top:

```html
<div class="flex gap-6">
    <x-tabs.navlist :$tabs :$currentTab :query-as="$queryTabAs" class="w-40 shrink-0" />

    <x-tabs.content :panels="$tabPanels" :$currentTab orientation="vertical" class="min-w-0 flex-1" />
</div>
```

```blade
<livewire:pages.local.examples.tabs query-as="navlist" bar="navlist" />
```

All bars take the same props. `aria-label` gives the bar a name for screen readers. Use it when the page has more than one bar, so the user can tell them apart:

```blade
<livewire:pages.local.examples.tabs query-as="labelled" aria-label="Profile sections" />
```

Other attributes go to the Flux component, except `x-data`: the bar has its own Alpine behavior. For a bar in a card, see [Card Tabs](card.tabs.md).

## Content

`<x-tabs.content>` renders the content of `$tabPanels`. The content of a tab is a Livewire component or a view:

```php
Tab::make('overview')->content(Overview::class),
Tab::make('help')->content(view('projects.help')),
Tab::make('owner')->content(fn (): string => $this->project->isCompany() ? CompanyOwner::class : PersonOwner::class),
```

A Closure runs only when the tab renders. A string that is not a Livewire component class throws a `LogicException`, and so does a tab without content when it renders.

`params()` gives the content its props: the mount parameters of a component, or the data of a view. `:params` on `<x-tabs.content>` gives props to every tab, and they win over the props of a tab.

```php
Tab::make('members')->content(Members::class)->params(['role' => 'editor']),
```

```html
<x-tabs.content :panels="$tabPanels" :$currentTab :params="['project' => $project]" />
```

The content enters with a short slide, from the side or, with `orientation="vertical"`, from the top. The slide stays off when the system asks for reduced motion.

## Load Fresh and Keep Alive

By default, a tab loads fresh. When the user leaves it, its content goes away. When the user comes back, the content mounts again from the start.

`keepAlive()` keeps the content of a tab. After the first visit, it stays mounted and hidden. When the user comes back, the tab shows at once, with its state and the input that the user did not save. Both kinds can be in one tab set.

In the example, Profile stays alive and Notes loads fresh. Type in both fields, open the other tab, and come back.

```blade
<livewire:pages.local.examples.tabs query-as="keep-alive" />
```

Only a Livewire component can stay alive. A view renders inside the component that owns the tab set and has no state of its own, so `keepAlive()` on a view throws a `LogicException`. A kept-alive tab that becomes hidden or disabled leaves the page.

## Lazy

`lazy()` mounts the Livewire content with [Livewire lazy loading](https://livewire.laravel.com/docs/lazy). The tab shows the placeholder of the component at once, and the content loads in a second request. Use it for content that takes long to load. It has no effect on a view.

```php
Tab::make('reports')->content(Reports::class)->lazy(),
```

## Hidden and Disabled

`hidden()` takes a tab out of the bar. `visible()` does the same with the opposite value. They share one state, so the last call wins. `disabled()` keeps a tab in the bar, greyed out, without a link. A hidden or a disabled tab never opens. Each takes a bool or a Closure:

```php
Tab::make('audit')->content(Audit::class)->visible(fn (): bool => auth()->user()->can('audit.view')),
Tab::make('billing')->content(Billing::class)->disabled(! $this->project->hasPlan()),
```

The Closure runs on every request. So when the current tab becomes hidden or disabled, the tab set opens the default tab.

Without an argument, `hidden()`, `visible()`, and `disabled()` take `true`.

Hiding or disabling a tab only decides what the tab set shows. It does not protect the content. The content component must authorize the user in its own `mount()` and in its actions.

## Label, Icon, Badge, and Tooltip

`label()` is the text of a tab. Without it, the tab shows its slug. `icon()` takes the name of a [Flux icon](https://fluxui.dev/components/icon). `badge()` adds a small badge after the label, and `badgeColor()` takes a [Flux badge](https://fluxui.dev/components/badge) color.

`tooltip()` shows a tooltip on the tab. `always` shows on every tab state. `disabled` shows only while the tab is disabled, so it can tell the user why. A disabled tab without `disabled` text shows the `always` text. The Billing tab in the examples is disabled and has a tooltip.

```php
Tab::make('tasks')
    ->label((string) __('projects.tabs.tasks'))
    ->icon('check-circle')
    ->badge(fn (): string => (string) $this->project->openTasks()->count())
    ->badgeColor('blue')
    ->tooltip(always: (string) __('projects.tabs.tasks_tooltip'), disabled: (string) __('projects.tabs.tasks_locked')),
```

`icon()`, `badge()`, and both tooltips take a Closure too.

## Title and Description

`title()` and `description()` hold text for the page, such as a heading above the content. No bar shows them. Put them where the page needs them:

```html
<flux:heading size="lg">{{ $currentTab->resolvedTitle() }}</flux:heading>
<flux:text>{{ $currentTab->resolvedDescription() }}</flux:text>
```

## Nested Tab Sets

A tab set inside the content of a tab is a Livewire component of its own. It uses the trait with its own key from `queryTabAs()`. It needs nothing else: a deep link such as `?tab=settings&tab-settings=notes` opens both levels. The inner set also reads its key when it mounts later, after a switch of the outer set.

When the user leaves the outer tab, the inner key stays in the URL. So when the user comes back, the inner set opens the tab that the user had.

A nested set with no available tab aborts with 404 when it mounts, also when it mounts after a switch of the outer set. So give every nested set at least one tab that is always available.

```blade
<livewire:pages.local.examples.tabs query-as="outer" nested />
```

## One Small Component per Tab Set

A switch renders the component that owns the tab set again. So put each tab set in its own small component, and let the page around it hold the rest. Then a switch sends and renders only the tab set, not the whole page. Pass the record of the page to the tab set as a prop, and to the tabs with `params()`.

One component holds one tab set. A second set on the same level needs its own component and its own key.

## Reference

### Tab

`Tab::make()` takes the slug of the tab. The slug is the value in the URL. An empty slug throws an `InvalidArgumentException`. So does a slug that is a JSON value, such as `2`, `1e3`, `true`, or `null`, because Livewire decodes such values from the URL.

| Method          | Description                                                                                         |
|-----------------|-----------------------------------------------------------------------------------------------------|
| `label()`       | The text of the tab. The default is the slug.                                                       |
| `title()`       | Text for a heading on the page. The bars do not show it.                                            |
| `description()` | Text for the page under the title. The bars do not show it.                                         |
| `icon()`        | A Flux icon name, or a Closure.                                                                     |
| `badge()`       | A badge after the label: a string, an int, or a Closure.                                            |
| `badgeColor()`  | The color of the badge.                                                                             |
| `tooltip()`     | A tooltip: `always`, and `disabled` for a disabled tab. Each takes a Closure too.                   |
| `hidden()`      | Takes the tab out of the bar. A bool or a Closure. The default is `true`.                           |
| `visible()`     | The opposite of `hidden()`. The last call wins. The default is `true`.                              |
| `disabled()`    | Greys out the tab and stops it from opening. A bool or a Closure. The default is `true`.            |
| `content()`     | A Livewire component class, a view, or a Closure that returns one of them.                          |
| `params()`      | Props for the content. An array or a Closure.                                                       |
| `keepAlive()`   | Keeps a Livewire content mounted and hidden after the first visit.                                  |
| `lazy()`        | Mounts a Livewire content with Livewire lazy loading.                                               |

### Trait

| Member                 | Description                                                                                         |
|------------------------|-----------------------------------------------------------------------------------------------------|
| `tabs()`               | Required. Returns the tabs of the set.                                                              |
| `queryTabAs()`         | The URL key. The default is `tab`.                                                                  |
| `switchTab()`          | Opens a tab by its slug. It ignores a tab that cannot open.                                         |
| `$tab`                 | The slug of the current tab, or an empty string for the default tab.                                |
| `$this->currentTab`    | The current `Tab`.                                                                                  |

### Bar Props

`<x-tabs>`, `<x-tabs.navbar>`, and `<x-tabs.navlist>` take the same props.

| Prop          | Default | Description                                                     |
|---------------|---------|-----------------------------------------------------------------|
| `tabs`        |         | Required. The tabs, `$tabs` from the trait.                     |
| `current-tab` |         | The current tab, `$currentTab` from the trait.                  |
| `query-as`    | `tab`   | The URL key, `$queryTabAs` from the trait.                      |
| `aria-label`  |         | The name of the bar for screen readers.                         |

All other attributes go to the Flux component, such as `variant` and `class`. Do not set `x-data`.

### Content Props

| Prop          | Default      | Description                                                         |
|---------------|--------------|---------------------------------------------------------------------|
| `panels`      |              | Required. The tabs to render, `$tabPanels` from the trait.          |
| `current-tab` |              | The current tab, `$currentTab` from the trait.                      |
| `orientation` | `horizontal` | The direction of the slide: `horizontal` or `vertical`.             |
| `params`      | `[]`         | Props for the content of every tab. They win over the tab props.    |

All other attributes go to the element around the content, such as `class`.
