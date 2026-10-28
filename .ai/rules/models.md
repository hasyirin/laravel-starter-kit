---
paths:
  - 'app/Domain/**/Models/*.php'
---

# Models

## Order model members
Order model members like this:
1. `$fillable`
2. `$hidden`
3. `$attributes`
4. `casts()`
5. `newFactory()`
6. Relationships
7. Other methods
8. Attribute accessors and mutators (methods that return `Attribute`)

## Extend the shared base Model
Extend `App\Domain\Shared\Common\Models\Model`, give its table `softDeletesTz()`, and add a morph alias for it in `EloquentServiceProvider`. If the model must extend another class, such as `Authenticatable` or a Spatie model, use the traits in `Shared/Common/Concerns/Models` instead.

## Use model properties, not Laravel 13 attributes
Declare `$fillable`, `$hidden`, `$guarded`, `$visible`, and `$appends` as properties, not as class attributes such as `#[Fillable]` or `#[Hidden]`.

## Document columns with @property docblocks
Add a `@property` docblock for each column the model adds, typed as its cast returns it. Type dates as `CarbonImmutable`, not `Carbon`.

## Keep system flags out of $fillable
Keep system-controlled flags such as `is_owner` and `is_system` out of `$fillable`. Default them to `false` in `$attributes`, cast them to `boolean`, and set them only with `forceFill()` or inside `unguarded()`.

## Link models and factories both ways
Put a model's factory in the sibling `Factories` folder. The model's `newFactory()` returns `XFactory::new()`, and the factory sets `protected $model = X::class`.

## Use #[ObservedBy] observers for model events
Put model event logic in an observer in the sibling `Observers` folder, and attach it with `#[ObservedBy(XObserver::class)]`, not `booted()`. Enforce invariants in the observer by throwing a `LogicException` with a full-sentence message.

## Give a model media through the kit
Implement `App\Domain\Shared\Common\Contracts\Models\HasMedia` and use `App\Domain\Shared\Common\Concerns\Models\InteractsWithMedia`, not Spatie's `HasMedia` and `InteractsWithMedia`. The kit's trait makes the `preview` conversion that the file upload shows, and it takes enum cases as collection names. A model that writes its own `registerMediaConversions()` adds the `preview` again.
