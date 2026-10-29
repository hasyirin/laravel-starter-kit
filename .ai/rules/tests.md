---
paths:
  - 'tests/**'
---

# Tests

## Place tests by code path or by page
Put a test for code in `app/` at the matching path under `tests/Feature/`, for example `tests/Feature/Domain/Shared/Organization/Models/OwnerTest.php`. Put page-flow tests under `tests/Feature/Pages/`, for example `tests/Feature/Pages/Auth/`. Put a test that needs JavaScript in a real browser under `tests/Browser/`, for example `tests/Browser/TabsTest.php` for the tab bar. Test the server side in `tests/Feature/` first, and add a browser test only for behavior that runs in the browser. Put a test for a Blade component in `resources/views/components` under `tests/Feature/Views/Components/`, for example `InputTest.php` for `<x-input>` and `Input/NumberTest.php` for `<x-input.number>`.

## Test shared code through fixture classes
Test shared base classes, traits, and enum concerns through fixture classes in `tests/Fixtures/<Kind>/` (namespace `Tests\Fixtures`). A fixture model needs its table created in `beforeEach()` and a morph alias from `Relation::morphMap()`.

## Use helpers instead of facades
Call the `auth()`, `config()`, `cache()`, `context()`, and `logger()` helpers instead of their facades. Use a facade only when no helper covers the call, such as `Gate::before()` or `Log::spy()`.

## Name each test as a rule
A module doc can list the test names of the module as its rules. Write each name as one rule in plain words, for example `a system role cannot be deleted`.
