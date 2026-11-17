---
paths:
  - 'app/Domain/**/Observers/*.php'
---

# Observers

## Put model event logic in an observer
Put model event logic in an observer in the `Observers` folder of the model's context. Attach each observer where the model's code looks for it, so that no second observer runs: `#[ObservedBy(XObserver::class)]` on a kit model, not `booted()`, and the package's config key on a package model, such as `media-library.media_observer`. Enforce invariants in the observer by throwing a `LogicException` with a full-sentence message.
