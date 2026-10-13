# Issue and technician workflow

`App\Services\IssueWorkflow` owns the legal status transitions, authorization, row locks, transactions, status history, and after-commit events. Livewire components call the service and do not set the issue's workflow status directly.

## Status transitions

- reported -> verified, rejected, or merged
- verified -> assigned, rejected, or merged
- assigned -> in_progress or merged
- in_progress -> on_hold or resolved
- on_hold -> in_progress
- resolved -> closed or reopened
- closed -> reopened
- reopened -> verified or merged
- rejected and merged are terminal

Reject, hold, reopen, and merge actions require a reason. A merge stores `merged_into_issue_id`, moves reporter/affected/follower links to the canonical issue, releases active assignments, and records the source issue's transition to `merged`.

## Data contracts

- `issue_user` is the source of reporter, affected, and follower membership; there is no duplicate `reporter_id` on `issues`.
- `assignments` records `issue_id`, `team_id`, `technician_id`, `assigned_by`, `assigned_at`, `accepted_at`, and `released_at`.
- Technicians have the single `technician` role and belong to teams such as Facility or IT Support through `team_user`.
- `comments(issue_id, user_id, body)` stores technician progress updates.
- `resolutions` stores the issue, assignment, resolver, root cause, action taken, parts used, and minutes spent.
- `feedback` records the reporter's 1–5 rating when confirming and closing an issue.
- A newly reported issue gets its initial append-only `status_histories` row after the reporter pivot is saved.

Verification also records the selected priority. Status events are emitted after commit and queued database notifications go to participants and the coordinator team.

## Local verification

Run `php artisan test` after installing dependencies and configuring the database. `tests/Unit/IssueStatusTest.php` covers legal transitions and guards against illegal jumps such as reported -> closed.
