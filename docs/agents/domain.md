# Domain Docs

How the engineering skills should consume this repo's domain documentation when exploring the codebase.

## Before exploring, read these

- **`GLOSSARY.md`** at the repo root.
- **`docs/adr/kit/`**: the decisions of the starter kit. Read the ADRs that touch the area you are about to work in.
- **`docs/adr/`**: the decisions of the app. Read the ADRs that touch the area you are about to work in.

If one of these does not exist, **proceed silently**. Do not flag its absence, and do not suggest creating it upfront. The `/domain-modeling` skill creates these files lazily, when a term or a decision gets resolved. `/grill-with-docs` and `/improve-codebase-architecture` run that skill.

## File structure

```
/
├── GLOSSARY.md
├── docs/adr/
│   ├── kit/
│   │   ├── 0001-<decision>.md
│   │   └── 0002-<decision>.md
│   ├── 0001-<decision>.md
│   └── 0002-<decision>.md
└── app/
```

## Write a new ADR in the right folder

Apps built from the starter kit keep pulling updates from it. So the kit and each app keep their ADRs apart, and each folder has its own numbers.

- In an app, add a new ADR to `docs/adr/`, numbered after the app's last ADR.
- In an app, never add to, edit, or renumber `docs/adr/kit/`. The next pull from the kit changes it.
- To change a kit decision in an app, write an app ADR that names the kit ADR it replaces.
- In the starter kit repository itself, add a new ADR to `docs/adr/kit/`, numbered after the kit's last ADR. The origin remote of the kit repository is hasyirin/laravel-starter-kit.

Call a kit ADR "kit ADR-0013" and an app ADR "ADR-0013", so the two sets of numbers do not mix.

## Use the glossary's vocabulary

When your output names a domain concept, use the term that `GLOSSARY.md` defines, not a synonym that it avoids. This applies to an issue title, a refactor proposal, a hypothesis, and a test name.

If the glossary does not have the concept you need yet, treat that as a signal. Either you are inventing language that the project does not use, so reconsider it. Or the glossary has a real gap, so note it for `/domain-modeling`.

## Flag ADR conflicts

If your output contradicts an existing ADR, surface the conflict explicitly:

> _Contradicts ADR-0099 (event-sourced orders), but worth reopening because…_

For a kit ADR, write "kit ADR-0099".
