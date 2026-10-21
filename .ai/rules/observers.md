---
paths:
  - 'app/Domain/**/Observers/*.php'
---

# Observers

## Use #[ObservedBy] observers for model events
Put model event logic in an observer in the sibling `Observers` folder, and attach it with `#[ObservedBy(XObserver::class)]`, not `booted()`. Enforce invariants in the observer by throwing a `LogicException` with a full-sentence message.
