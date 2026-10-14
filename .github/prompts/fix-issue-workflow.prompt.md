---
name: "Fix Issue Workflow"
description: "Research an issue, create an approval-gated plan, then implement and verify it phase by phase"
argument-hint: "Issue URL or issue number"
agent: "agent"
---

Resolve the following issue using the research -> plan -> approve -> execute workflow.

Target issue: `${input:issue}`

Follow the [workspace instructions](../../AGENTS.md) throughout the workflow.

## 1. Research

- Retrieve the complete issue description, acceptance criteria, comments, labels, and linked work.
- Inspect the current worktree before making changes and preserve unrelated edits.
- Trace the smallest relevant code path from the owning implementation to its callers and tests.
- Read nearby tests, project documentation, and history only when they resolve a concrete ambiguity.
- Identify the likely root cause or implementation gap, affected contracts, security concerns, and regression surface.
- Do not edit implementation files during this stage.

## 2. Plan

- Ask one concise round of questions only when requirements cannot be inferred safely.
- Produce an implementation-ready plan divided into independently verifiable phases.
- For each phase, list the exact files or symbols involved, intended behavior, dependencies, and focused validation.
- Include explicit non-goals, compatibility constraints, risks, and the final full-suite command.
- Save the plan as `plan-issue-<number>.md` in the workspace root, replacing `<number>` with the issue number.
- Present the plan and stop. Ask for explicit approval or requested revisions.

Do not write implementation code, modify tests, or execute the plan before the user approves it.

## 3. Approval

- Treat clear approval such as `Approve`, `Start implementation`, or `Execute phase 1` as authorization.
- If revisions are requested, update the plan and ask for approval again.
- Approval covers only the documented plan. If implementation reveals a material scope change, update the plan and request approval before continuing.

## 4. Execute

- Execute only the approved phase or phases requested by the user.
- Before each phase, state its scope and the focused check that can falsify the proposed change.
- Make the smallest grounded edit, then run the narrowest relevant test immediately.
- Repair failures caused by the change before proceeding. Do not absorb unrelated failures into the issue.
- Update the plan checklist as each task is completed.
- Preserve public APIs, stored formats, security guarantees, and deployment behavior unless the approved plan explicitly changes them.
- Add focused regression tests for bug fixes and security boundaries.
- After the final phase, run the repository's full required test suite and review the complete diff for unrelated changes or generated artifacts.

## Completion Report

Report:

- Root cause or implementation gap
- Files and behavior changed
- Focused and full validation results
- Remaining risks, skipped checks, or unrelated pre-existing failures
- Whether the issue's acceptance criteria are fully satisfied