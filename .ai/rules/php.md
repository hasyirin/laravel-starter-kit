---
paths:
  - '**/*.php'
---

# Php

## Prefer collections over arrays
Use Laravel collections instead of plain arrays. When code must keep an array, such as a Livewire property or a return type, change it through a collection. Then turn it back with `->all()`, for example `collect($names)->reject(...)->values()->all()`. Do not change arrays with `array_filter()`, `array_map()`, `array_diff()`, or similar functions.

## Write comments in the spirit of ASD-STE100
Write each comment by the rules in `.ai/rules/md.md`: short sentences, one idea per sentence, the active voice, and common words. Keep code names exact.

## Return early with guard clauses
Check the edge cases first and return early, so the main path stays at the lowest indent. Do not wrap the main path in an `if`/`else`. Do not hide an edge case in a ternary, such as `return $self ? $locked : [];`; write `if (! $self) { return []; }` and then the main path.

## Write code that reads easily
Choose readable code over short code. Never nest ternaries: use guard clauses, `if` statements, or `match`. Give each step its own line instead of a long chain on one line. If a variable needs a docblock, such as `/** @var User $actor */`, and the line above it declares another variable, put an empty line between them.

## Choose by enum case with match
When an enum method gives a result for each case, use `match ($this)`. Do not use `if ($this === self::X)`. Give each case its own arm. Use `default` only when most cases give the same result. Without `default`, PHPStan reports a new case that has no arm. A case that needs several steps of logic can keep its own `if`. Static methods are not part of this rule.

```php
return match ($this) {
    self::Sending => 'amber',
    self::Sent => 'green',
    self::Failed => 'red',
};
```

## Name booleans without is
Name a boolean variable or parameter with the plain word, such as `$self`, `$unchanged`, or `$disabled`, not `$isSelf`. Name a method that returns a boolean in the same way, such as `editable()`, not `canChange()` or `isEditable()`. Keep the `is` or `has` prefix only in these cases:
- The plain word is already in use, by a fluent setter of the same name (`disabled()` sets the state, so `isDisabled()` reads it) or by a property.
- The method overrides a framework or package method, such as `hasRole()`.
- The method tests its argument, such as `isDate($value)`. Without the prefix, `date($value)` reads like it returns a date.

Start a flag that shows a modal or another part of the page with `showing`. Then name the part, such as `$showingDeleteModal` or `$showingVerificationStep`. If the class name already names the part, such as `DeleteModal`, write only `$showing`. The name then does not repeat the class. Do not write `$deleteOpen` or `$verifying`: the first does not say what opens, and the second reads like a process.

## Name methods short, without repeating the class
Give each method and function the shortest name that is clear in its class. Leave out the words that the class name already says: `DeleteUserForm::delete()`, not `deleteUser()`, and `Preferences::save()`, not `updatePreferences()`. Add a noun only when the class does several things, such as `Security::updatePassword()` and `deletePasskey()`. Keep a name that a framework or contract sets, such as a Livewire hook or `toResponse()`. A trait method becomes part of other classes, so name it for them, such as `userInformationRules()`. A global function, such as a helper in a Pest test file, keeps the words that make it unique.

## Mark secret parameters with #[SensitiveParameter]
Add `#[SensitiveParameter]` to each parameter that holds a phone number, a password, a one-time code, a password reset token, or an SMS text. A stack trace then shows `SensitiveParameterValue` instead of the value, so the logs do not get it. Mark an interface parameter in the interface and in each class that implements it. Also mark promoted constructor parameters and closure parameters. Import the attribute with `use SensitiveParameter;`. `tests/Feature/SensitiveParameterTest.php` checks the methods in `app/` by parameter name. It cannot see closures.
