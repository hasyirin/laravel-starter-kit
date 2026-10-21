---
paths:
  - 'app/Domain/**/Factories/*.php'
---

# Factories

## Link models and factories both ways
Put a model's factory in the sibling `Factories` folder. The model's `newFactory()` returns `XFactory::new()`, and the factory sets `protected $model = X::class`.
