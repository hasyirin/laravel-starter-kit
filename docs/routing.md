# Routes and Access Areas

The app has four areas, and each area has its own route file. A fifth file, `routes/local.php`, holds tools for developers, and it is not an area. `routes/web.php` loads the five files and gives each one its middleware, URL prefix, and route name prefix. All five also get the `web` middleware group.

| Area     | File                  | Who can open it                    | URL prefix  | Name prefix | Middleware                                |
|----------|-----------------------|------------------------------------|-------------|-------------|-------------------------------------------|
| Guest    | `routes/guest.php`    | Everyone                           | None        | None        | None                                      |
| App      | `routes/app.php`      | Signed-in users                    | None        | None        | `auth`                                    |
| Internal | `routes/internal.php` | Users with `internal.access`       | `/internal` | `internal.` | `auth`, `verified`, `can:internal.access` |
| System   | `routes/system.php`   | Users with `system.access`         | `/system`   | `system.`   | `auth`, `verified`, `can:system.access`   |

Fortify registers the routes for login, registration, password reset, email verification, two-factor authentication, and passkeys. Do not add them to `guest.php`.

## Choose the Area

- **Guest**: pages and endpoints that everyone must reach, signed in or not. Examples are the home page and `/.well-known/passkey-endpoints`. Do not add the `guest` middleware to this file. That middleware sends signed-in users away, so these routes would stop working for them.
- **App**: pages for every signed-in user, such as the dashboard, and the `media.show` route, which serves stored files (see [File Upload](components/file-upload.md#stored-files)). Add the `verified` middleware to a route that needs a verified email address.
- **Internal**: the daily work of the staff, such as users, roles, orders, and reports.
- **System**: the tools that run the app, such as Horizon.

Put tools for developers, such as the live component docs at `/docs/components`, in `routes/local.php`. Everyone can open them in the local environment, and the routes get no URL or name prefix. In every other environment, `EnsureLocalEnvironment` answers 404, so these routes stay registered but never open in production.

## Add a Route

Write the route without the prefixes of its area. The group in `routes/web.php` adds them:

```php
// routes/internal.php
Route::livewire('orders', Orders\Index::class)->name('orders.index');

// URL: /internal/orders
// Name: internal.orders.index
```

When an area grows, give each module its own file in a folder named after the area. Load that file from the area file:

```php
// routes/internal.php
Route::prefix('orders')->name('orders.')->group(__DIR__.'/internal/orders.php');
```

Put page tests in `tests/Feature/Pages/`. The internal and system areas have their own folders: `tests/Feature/Pages/Internal/` and `tests/Feature/Pages/System/`.

## Give Access

- `config/system.php` defines `internal.access` and `system.access`. `PermissionSeeder` creates them.
- A user gets their permissions from their active profile, and from roles and permissions given to the user directly. See **Profiles** in the README.
- The two areas are separate. `system.access` does not open the internal area, so give both permissions to a user who needs both areas.
- The `system-admin` role has the `bypass-authorization` permission. Its users pass every check, so they can open both areas.
- Other permissions control the parts of each area: `internal.controls.roles.*`, `internal.controls.users.*`, and `internal.controls.organizations.*` for the roles, users, and organizations pages, and `system.horizon.view` for Horizon.

## After Login

After a user logs in with a password, a two-factor code, or a passkey, the app sends them to the highest area that they can open:

| The user has                          | The app sends them to                                                |
|---------------------------------------|----------------------------------------------------------------------|
| `system.access` and `internal.access` | The system dashboard, `/system`                                      |
| `system.access` only                  | The system dashboard, `/system`                                      |
| `internal.access` only                | The internal dashboard, `/internal`                                  |
| Neither                               | The Fortify home path, `/dashboard` (`home` in `config/fortify.php`) |

If the user tried to open a page before they logged in, the app sends them to that page instead.

The three login responses are in `app/Http/Responses`, and `FortifyServiceProvider` binds them. `LoginResponse::highestAreaUrl()` sets the order.

## Pages and Layouts

Full-page Livewire components extend `App\Livewire\Pages\Shared\Page`. It gives each page the layout of the area that its route is in:

| Route                                   | Layout                                       |
|-----------------------------------------|----------------------------------------------|
| Routes in `routes/local.php`            | `resources/views/layouts/local.blade.php`    |
| `system.*`                              | `resources/views/layouts/system.blade.php`   |
| `internal.*`                            | `resources/views/layouts/internal.blade.php` |
| Other routes with the `auth` middleware | `resources/views/layouts/app.blade.php`      |
| All other routes                        | `resources/views/layouts/guest.blade.php`    |

`Page` finds a local route by its `EnsureLocalEnvironment` middleware. The local layout works for guests too: it shows the user menu only to a signed-in user. For all other routes, `Page` gets the area from `Area::current()`.

A guest page keeps the guest layout for signed-in users too. A page that more than one area uses gets the layout of each area.

The pages of Fortify, such as login, do not extend `Page`. They use `resources/views/layouts/auth.blade.php`.

## Settings Pages

The app, internal, and system areas each have the four settings pages: user information, preferences, security, and appearance. `routes/settings.php` lists the pages once, and `routes/app.php`, `routes/internal.php`, and `routes/system.php` each load it. So each area gives the pages its own URLs and route names, and each page opens in the layout of its area:

| Area     | Security page URL             | Route name               |
|----------|-------------------------------|--------------------------|
| App      | `/settings/security`          | `security.edit`          |
| Internal | `/internal/settings/security` | `internal.security.edit` |
| System   | `/system/settings/security`   | `system.security.edit`   |

The three areas use the same components, in `app/Livewire/Settings`. Their links stay in the area of the page. This includes the settings menu and the return to the security page after the user confirms their password. `/.well-known/passkey-endpoints` gives the app security page, because it is not in an area.

Add a new settings page to `routes/settings.php`, so that all three areas get it.

## Navigation

- The app, internal, and system areas each have a navigation: `app/Livewire/Layouts/*/Navigation.php`. Below the spacer, each navigation links to the other areas:
  - The app navigation links to Internal and System.
  - The internal navigation links to App and System.
  - The system navigation links to App and Internal.
- A navigation shows the Internal or System link only to users who have the matching permission. The App link goes back to the app dashboard, and it always shows.
- In the local environment, each area navigation also shows a Docs link to `/docs/components`.
- The local pages have their own navigation, `app/Livewire/Layouts/Local/Navigation.php`. It lists the component docs pages. Below the spacer, it links to App, Internal, and System, each only for users who can open that area. A guest sees no area links.
- The logo opens the dashboard of its own area. On the local pages, it opens `/docs/components`.
- Each area has a settings route: `settings`, `internal.settings`, and `system.settings`. Each one opens the user information page of its own area. The user menu links to the settings route of the current area.

### Sidebar or Top Bar

A navigation shows its items in one of two styles, the cases of `App\Livewire\Components\Navigation\Style`:

- `Style::Sidebar` puts the items in a sidebar at the side of the page. The internal, system, and local areas always use it.
- `Style::Topbar` puts the items in a bar at the top of the page. Use it for an app that the public uses, and keep the sidebar for staff.

The app area uses the style in `system.navigation.app`. The default is `Style::Sidebar`. To use the top bar, change the value in `config/system.php`:

```php
'navigation' => [
    'app' => Style::Topbar,
],
```

Both styles show the same items, from the same `items()` method. In the top bar:

- The logo comes first, then the items, then the user menu. The spacer pushes the items after it to the right, such as the links to the other areas.
- An item with children opens a dropdown. A child with its own children opens a submenu in that dropdown.
- A group with a label opens a dropdown. A group without a label shows a vertical line before its items.
- On small screens, the bar hides the items. The menu button opens them in a drawer that looks like the sidebar.
- A page that fills the `header` section gets a second bar below the top bar.

## Horizon

The Horizon dashboard is at `/system/horizon`. Set `HORIZON_PATH` to move it. Horizon registers its own routes, so the middleware of the system area does not apply to them. Horizon checks `system.horizon.view` itself, except in the local environment, where everyone can open it.

The system sidebar links to Horizon for users with `system.horizon.view`. The link loads the whole page, without `wire:navigate`, because Horizon is not a Livewire page.

## Rules

- **Give each URL one route.** Without the route cache, the last route for a URL wins. With the route cache, the first one wins, and `php artisan optimize` turns the cache on. So a second route for the same URL can work in development and fail in production.
- **In the internal and system areas, write the full path of a redirect target.** The group adds its URL prefix to the route, but not to the target. So in `routes/internal.php`, write `Route::redirect('settings', '/internal/settings/user-information')`. Start the target with `/`, because the browser reads a target without `/` relative to the current URL.
- **Get the current area from `Area::current()`.** Do not use `request()->routeIs()` to find the area. A Livewire update request has the route of Livewire, not the route of the page. So after an action, such as a save, `request()->routeIs('internal.*')` is false. `Area::current()` finds the route of the page in both cases. Use `$area->route('security.edit')` to get the name of a route in that area.
