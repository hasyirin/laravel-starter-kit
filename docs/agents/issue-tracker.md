# Issue tracker: GitHub

Issues and specs for this repo live as GitHub issues. Use the `gh` CLI for all operations.

## Conventions

- **Create an issue**: `gh issue create --title "..." --body "..."`. Use a heredoc for multi-line bodies.
- **Read an issue**: `gh issue view <number> --json number,title,body,labels,comments`.
- **List issues**: `gh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'`. Set the `--label` and `--state` filters as necessary.
- **Make an issue a sub-issue of a parent**: `gh issue create --parent <parent> ...`, or `gh issue edit <parent> --add-sub-issue <child>` afterwards (`gh` 2.94+). With an older `gh`, use `gh api --method POST repos/<owner>/<repo>/issues/<parent>/sub_issues -F sub_issue_id=<child-db-id>`. `<child-db-id>` is a database id, as in **Blocking** below. Without sub-issues, put `Part of #<parent>` at the top of the child body.
- **Comment on an issue**: `gh issue comment <number> --body "..."`
- **Apply / remove labels**: `gh issue edit <number> --add-label "..."` / `--remove-label "..."`
- **Close**: `gh issue close <number> --comment "..."`

Infer the repo from `git remote -v`. Inside a clone, `gh` does this automatically.

## Pull requests as a triage surface

**PRs as a request surface: no.** _(Set it to `yes` if this repo treats external PRs as feature requests. `/triage` reads this flag.)_

If the flag is `yes`, PRs go through the same labels and states as issues. Use the `gh pr` equivalents:

- **Read a PR**: `gh pr view <number> --comments`, and `gh pr diff <number>` for the diff.
- **List external PRs for triage**: `gh api --paginate 'repos/{owner}/{repo}/pulls?state=open' --jq '.[] | select(.author_association | IN("OWNER","MEMBER","COLLABORATOR") | not) | {number, title, author: .user.login, author_association, labels: [.labels[].name]}'`.
- **Comment / label / close**: `gh pr comment`, `gh pr edit --add-label`/`--remove-label`, `gh pr close`.

Issues and PRs share one number space on GitHub, so a bare `#42` can be either. To resolve it, run `gh pr view 42`. If that fails, run `gh issue view 42`.

## When a skill says "publish to the issue tracker"

Create a GitHub issue.

## When a skill says "fetch the relevant ticket"

Read it as in **Read an issue** above.

## Wayfinding operations

`/wayfinder` uses these operations. The **map** is a single issue with **child** issues as tickets.

- **Map**: a single issue labelled `wayfinder:map`, holding the Notes / Decisions-so-far / Fog body. `gh issue create --label wayfinder:map`.
- **Child ticket**: an issue linked to the map as a GitHub sub-issue (see **Make an issue a sub-issue of a parent**). If sub-issues are not enabled, add the child to a task list in the map body. In that case, also put `Part of #<map>` at the top of the child body. Labels: `wayfinder:<type>` (`research`/`prototype`/`grilling`/`task`). A claim assigns the ticket to the driving dev.
- **Blocking**: GitHub's **native issue dependencies**, the canonical form that the GitHub UI shows. Add an edge with `gh api --method POST repos/<owner>/<repo>/issues/<child>/dependencies/blocked_by -F issue_id=<blocker-db-id>`. `<blocker-db-id>` is the numeric **database id** of the blocker: `gh api repos/<owner>/<repo>/issues/<n> --jq .id`. It is _not_ the `#number` or the `node_id`. `gh api repos/<owner>/<repo>/issues/<n> --jq .issue_dependencies_summary.blocked_by` gives the number of open blockers, which is the live gate. If dependencies are not available, put a `Blocked by: #<n>, #<n>` line at the top of the child body. A ticket is unblocked when every blocker is closed.
- **Frontier query**: list the open children of the map (`gh issue list --state open`, limited to the sub-issues or task list of the map). Drop each child that has an assignee or an open blocker. An open blocker shows as `issue_dependencies_summary.blocked_by > 0`, or as an open issue in the `Blocked by` line. The first remaining child in map order wins.
- **Claim**: `gh issue edit <n> --add-assignee @me`, the session's first write.
- **Resolve**: `gh issue comment <n> --body "<answer>"`, then `gh issue close <n>`, then append a context pointer (gist + link) to the map's Decisions-so-far.
