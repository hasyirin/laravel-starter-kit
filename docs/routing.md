# Routes and Access Areas

The app has four areas, and each area has its own route file. A fifth file holds tools for developers. `routes/web.php` loads the five files and gives each one its middleware, URL prefix, and route name prefix. All five also get the `web` middleware group.

| Area     | File                  | Who can open it                    | URL prefix  | Name prefix | Middleware                                |
|----------|-----------------------|------------------------------------|-------------|-------------|-------------------------------------------|
| Guest    | `routes/guest.php`    | Everyone                           | None        | None        | None                                      |
| App      | `routes/app.php`      | Signed-in users                    | None        | None        | `auth`                                    |
| Internal | `routes/internal.php` | Users with `internal.access`       | `/internal` | `internal.` | `auth`, `verified`, `can:internal.access` |
| Admin    | `routes/admin.php`    | Users with `admin.access`          | `/admin`    | `admin.`    | `auth`, `verified`, `can:admin.access`    |
| Local    | `routes/local.php`    | Everyone, in the local environment | None        | None        | `EnsureLocalEnvironment`                  |

Fortify registers the routes for login, registration, password reset, email verification, two-factor authentication, and passkeys. Do not add them to `guest.php`.

## Choose the Area

- **Guest**: pages and endpoints that everyone must reach, signed in or not. Examples are the home page and `/.well-known/passkey-endpoints`. Do not add the `guest` middleware to this file. That middleware sends signed-in users away, so these routes would stop working for them.
- **App**: pages for every signed-in user, such as the dashboard and the settings pages. Add the `verified` middleware to a route that needs a verified email address.
- **Internal**: the daily work of the staff, for example orders and reports.
- **Admin**: the administration of the system: users, roles, permissions, and Horizon.
- **Local**: tools for developers, such as the live component docs at `/docs/components`. `EnsureLocalEnvironment` answers 404 in every other environment, so these routes stay registered but never open in production.

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

Put page tests in `tests/Feature/Pages/`. The internal and admin areas have their own folders: `tests/Feature/Pages/Internal/` and `tests/Feature/Pages/Admin/`.

## Give Access

- `config/system.php` defines `internal.access` and `admin.access`. `PermissionSeeder` creates them.
- A user gets their permissions from their active profile, directly or through a role. See **Profiles** in the README.
- The two areas are separate. `admin.access` does not open the internal area, so give both permissions to a user who needs both areas.
- The super admin role has the `bypass-authorization` permission. Its users pass every check, so they can open both areas.
- Other permissions control the parts of the admin area: `admin.horizon.view` for Horizon, `admin.controls.roles.*` for roles, and `admin.controls.users.*` for users.

## After Login

After a user logs in with a password, a two-factor code, or a passkey, the app sends them to the highest area that they can open:

| The user has                         | The app sends them to                                                |
|--------------------------------------|----------------------------------------------------------------------|
| `admin.access` and `internal.access` | The admin dashboard, `/admin`                                        |
| `admin.access` only                  | The admin dashboard, `/admin`                                        |
| `internal.access` only               | The internal dashboard, `/internal`                                  |
| Neither                              | The Fortify home path, `/dashboard` (`home` in `config/fortify.php`) |

If the user tried to open a page before they logged in, the app sends them to that page instead.

The three login responses are in `app/Http/Responses`, and `FortifyServiceProvider` binds them. `LoginResponse::highestAreaUrl()` sets the order.

## Pages and Layouts

Full-page Livewire components extend `App\Livewire\Pages\Shared\Page`. It gives each page the layout of the area that its route is in:

| Route                                   | Layout                                       |
|-----------------------------------------|----------------------------------------------|
| `admin.*`                               | `resources/views/layouts/admin.blade.php`    |
| `internal.*`                            | `resources/views/layouts/internal.blade.php` |
| Other routes with the `auth` middleware | `resources/views/layouts/app.blade.php`      |
| All other routes                        | `resources/views/layouts/guest.blade.php`    |

A guest page keeps the guest layout for signed-in users too. A page that more than one area uses gets the layout of each area.

## Navigation

- The app, internal, and admin areas each have a sidebar: `app/Livewire/Layouts/*/Sidebar.php`. Below the spacer, each sidebar links to the other areas:
  - The app sidebar links to Internal and Admin.
  - The internal sidebar links to App and Admin.
  - The admin sidebar links to App and Internal.
- A sidebar shows the Internal or Admin link only to users who have the matching permission. The App link goes back to the app dashboard, and it always shows.
- The logo at the top of each sidebar opens the dashboard of its own area.
- Each area has a settings route: `settings`, `internal.settings`, and `admin.settings`. The user menu links to the settings route of the current area. For now, all three open the shared settings pages.

## Horizon

The Horizon dashboard is at `/admin/horizon`. Set `HORIZON_PATH` to move it. Horizon registers its own routes, so the middleware of the admin area does not apply to them. Horizon checks `admin.horizon.view` itself, except in the local environment, where everyone can open it.

## Rules

- **Give each URL one route.** Without the route cache, the last route for a URL wins. With the route cache, the first one wins, and `php artisan optimize` turns the cache on. So a second route for the same URL can work in development and fail in production.
- **In the internal and admin areas, start a redirect target with `/`.** In `routes/internal.php`, `Route::redirect('settings', 'settings/profile')` sends the browser to `/internal/settings/profile`, because the browser reads a target without `/` relative to the current URL. Write `Route::redirect('settings', '/settings/profile')`.
