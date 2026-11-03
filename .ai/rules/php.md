---
paths:
  - '**/*.php'
---

# Php

## Prefer collections over arrays
Use Laravel collections instead of plain arrays. When code must keep an array, such as a Livewire property or a return type, change it through a collection and turn it back with `->all()`, for example `collect($names)->reject(...)->values()->all()`. Do not change arrays with `array_filter()`, `array_map()`, `array_diff()`, or similar functions.

## Return early with guard clauses
Check the edge cases first and return early, so the main path stays at the lowest indent. Do not wrap the main path in an `if`/`else`. Do not hide an edge case in a ternary, such as `return $isSelf ? $locked : [];`; write `if (! $isSelf) { return []; }` and then the main path.

## Write code that reads easily
Choose readable code over short code. Never nest ternaries: use guard clauses, `if` statements, or `match`. Give each step its own line instead of a long chain on one line. When a variable needs a docblock, such as `/** @var User $actor */`, and another variable is declared just above it, put an empty line between them.
