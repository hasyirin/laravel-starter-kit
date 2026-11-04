# Domain Docs

How the engineering skills should consume this repo's domain documentation when exploring the codebase.

## Before exploring, read these

- **GLOSSARY.md** at the repo root.
- **docs/adr/**: read the ADRs that touch the area you are about to work in.

If one of these does not exist, **proceed silently**. Do not flag its absence, and do not suggest creating it upfront. The `/domain-modeling` skill creates these files lazily, when a term or a decision gets resolved. `/grill-with-docs` and `/improve-codebase-architecture` run that skill.

## File structure

```
/
├── GLOSSARY.md
├── docs/adr/
│   ├── 0001-<decision>.md
│   └── 0002-<decision>.md
└── app/
```

## Use the glossary's vocabulary

When your output names a domain concept, use the term that GLOSSARY.md defines, not a synonym that it avoids. This applies to an issue title, a refactor proposal, a hypothesis, and a test name.

If the glossary does not have the concept you need yet, treat that as a signal. Either you are inventing language that the project does not use, so reconsider it. Or the glossary has a real gap, so note it for `/domain-modeling`.

## Flag ADR conflicts

If your output contradicts an existing ADR, surface the conflict explicitly:

> _Contradicts ADR-0007 (event-sourced orders), but worth reopening because…_
