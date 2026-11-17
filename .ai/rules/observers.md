---
paths:
  - 'app/Domain/**/Observers/*.php'
---

# Observers

## Put model event logic in an observer
Put model event logic in an observer in the `Observers` folder of the model's context. Attach each observer where the model's code looks for it, so that no second observer runs. On a kit model, use `#[ObservedBy(XObserver::class)]`, not `booted()`. On a package model, use the package's config key, such as `media-library.media_observer`. Enforce invariants in the observer by throwing a `LogicException` with a full-sentence message.
