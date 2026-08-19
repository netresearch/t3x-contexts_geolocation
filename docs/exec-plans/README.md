# Execution Plans

Working directory for multi-step task plans used by AI agents (and humans) working on this repository.

- `active/` — plans currently being executed. One Markdown file per task: goal, ordered steps, current status.
- `completed/` — finished plans, kept for traceability of larger changes.

Conventions:

- File name: `YYYY-MM-DD-short-slug.md`.
- A plan is a scratchpad, not documentation — durable outcomes belong in `docs/`, `Documentation/`, or ADRs, not here.
- Delete or move a plan out of `active/` when the work lands; stale plans mislead the next agent.
