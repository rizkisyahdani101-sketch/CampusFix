<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\IssueStatus;
use App\Events\IssueStatusChanged;
use App\Exceptions\InvalidIssueTransition;
use App\Models\Assignment;
use App\Models\Feedback;
use App\Models\GuestIssueReporter;
use App\Models\Issue;
use App\Models\Resolution;
use App\Models\StatusHistory;
use App\Models\Team;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

final class IssueWorkflow
{
    /**
     * Record the reported status after the reporting service creates an issue.
     */
    public function recordInitialReported(
        Issue $issue,
        User $actor,
        ?string $reason = null,
    ): StatusHistory {
        return DB::transaction(function () use ($issue, $actor, $reason): StatusHistory {
            $lockedIssue = Issue::query()
                ->whereKey($issue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('recordInitialReported', $lockedIssue);

            if ($this->statusOf($lockedIssue) !== IssueStatus::Reported) {
                throw new DomainException('Only a reported issue can receive its initial status history.');
            }

            if (StatusHistory::query()->where('issue_id', $lockedIssue->getKey())->exists()) {
                throw new LogicException('Initial status history has already been recorded for this issue.');
            }

            $history = StatusHistory::query()->create([
                'issue_id' => $lockedIssue->getKey(),
                'from_status' => null,
                'to_status' => IssueStatus::Reported->value,
                'actor_id' => $actor->getKey(),
                'reason' => $this->normalizeReason($reason),
            ]);

            $this->dispatchAfterCommit($history);

            return $history;
        });
    }

    public function recordGuestInitialReported(Issue $issue): StatusHistory
    {
        return DB::transaction(function () use ($issue): StatusHistory {
            $lockedIssue = Issue::query()
                ->whereKey($issue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $hasVerifiedGuestReporter = GuestIssueReporter::query()
                ->where('issue_id', $lockedIssue->getKey())
                ->whereNotNull('email_verified_at')
                ->exists();

            if (! $hasVerifiedGuestReporter) {
                throw new AuthorizationException('A guest report must have a verified email before it enters the workflow.');
            }

            if ($this->statusOf($lockedIssue) !== IssueStatus::Reported) {
                throw new DomainException('Only a reported issue can receive its initial status history.');
            }

            if (StatusHistory::query()->where('issue_id', $lockedIssue->getKey())->exists()) {
                throw new LogicException('Initial status history has already been recorded for this issue.');
            }

            $history = StatusHistory::query()->create([
                'issue_id' => $lockedIssue->getKey(),
                'from_status' => null,
                'to_status' => IssueStatus::Reported->value,
                'actor_id' => null,
                'reason' => 'Guest reporter email verified.',
            ]);

            $this->dispatchAfterCommit($history);

            return $history;
        });
    }

    public function verify(
        Issue $issue,
        User $coordinator,
        ?string $reason = null,
        string $priority = 'medium',
    ): StatusHistory {
        if (! in_array($priority, ['low', 'medium', 'high', 'critical'], true)) {
            throw new InvalidArgumentException('The selected issue priority is invalid.');
        }

        return $this->transitionWithAttributes(
            $issue,
            IssueStatus::Verified,
            $coordinator,
            $reason,
            ['priority' => $priority],
        );
    }

    public function merge(Issue $duplicate, Issue $canonical, User $coordinator): StatusHistory
    {
        if ((string) $duplicate->getKey() === (string) $canonical->getKey()) {
            throw new DomainException('An issue cannot be merged into itself.');
        }

        return DB::transaction(function () use ($duplicate, $canonical, $coordinator): StatusHistory {
            $ids = [$duplicate->getKey(), $canonical->getKey()];
            sort($ids);

            $lockedIssues = Issue::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $source = $lockedIssues->get($duplicate->getKey());
            $target = $lockedIssues->get($canonical->getKey());

            if (! $source instanceof Issue || ! $target instanceof Issue) {
                throw new DomainException('Both issues must exist before they can be merged.');
            }

            $sourceStatus = $this->statusOf($source);
            $targetStatus = $this->statusOf($target);

            if (! $sourceStatus->canTransitionTo(IssueStatus::Merged)) {
                throw new InvalidIssueTransition($sourceStatus, IssueStatus::Merged);
            }

            if (
                $targetStatus === IssueStatus::Merged
                || $targetStatus === IssueStatus::Rejected
                || $target->merged_into_issue_id !== null
            ) {
                throw new DomainException('The canonical issue must be an active, non-merged issue.');
            }

            if (
                (string) $source->category_id !== (string) $target->category_id
                || (string) $source->location_id !== (string) $target->location_id
                || $this->normalizeLocationDetail($source->location_detail)
                    !== $this->normalizeLocationDetail($target->location_detail)
            ) {
                throw new DomainException('Duplicate issues must have the same category and exact location.');
            }

            Gate::forUser($coordinator)->authorize(
                'transitionStatus',
                [$source, IssueStatus::Merged],
            );

            $now = now();
            $participants = DB::table('issue_user')
                ->where('issue_id', $source->getKey())
                ->get(['user_id', 'relationship']);

            foreach ($participants as $participant) {
                DB::table('issue_user')->insertOrIgnore([
                    'issue_id' => $target->getKey(),
                    'user_id' => $participant->user_id,
                    'relationship' => $participant->relationship,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Assignment::query()
                ->where('issue_id', $source->getKey())
                ->whereNull('released_at')
                ->update(['released_at' => $now, 'updated_at' => $now]);

            return $this->transitionWithAttributes(
                $source,
                IssueStatus::Merged,
                $coordinator,
                'Duplicate of issue #'.$target->getKey(),
                ['merged_into_issue_id' => $target->getKey()],
            );
        });
    }

    public function reject(
        Issue $issue,
        User $coordinator,
        string $reason,
    ): StatusHistory {
        return $this->transition($issue, IssueStatus::Rejected, $coordinator, $reason);
    }

    public function assign(
        Issue $issue,
        Team $team,
        User $technician,
        User $coordinator,
    ): Assignment {
        return DB::transaction(function () use ($issue, $team, $technician, $coordinator): Assignment {
            $lockedIssue = Issue::query()
                ->whereKey($issue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $status = $this->statusOf($lockedIssue);

            if (! in_array($status, [IssueStatus::Verified, IssueStatus::Assigned], true)) {
                throw new InvalidIssueTransition($status, IssueStatus::Assigned);
            }

            Gate::forUser($coordinator)->authorize(
                'assignIssue',
                [$lockedIssue, $team, $technician],
            );

            $assignedAt = now();

            Assignment::query()
                ->where('issue_id', $lockedIssue->getKey())
                ->whereNull('released_at')
                ->lockForUpdate()
                ->get()
                ->each(static function (Assignment $activeAssignment) use ($assignedAt): void {
                    $activeAssignment->forceFill(['released_at' => $assignedAt])->saveOrFail();
                });

            $assignment = Assignment::query()->create([
                'issue_id' => $lockedIssue->getKey(),
                'team_id' => $team->getKey(),
                'technician_id' => $technician->getKey(),
                'assigned_by' => $coordinator->getKey(),
                'assigned_at' => $assignedAt,
            ]);

            if ($status === IssueStatus::Verified) {
                $this->transition($lockedIssue, IssueStatus::Assigned, $coordinator);
            }

            return $assignment;
        });
    }

    public function acceptAssignment(Assignment $assignment, User $technician): Assignment
    {
        return DB::transaction(function () use ($assignment, $technician): Assignment {
            $assignmentSnapshot = Assignment::query()
                ->whereKey($assignment->getKey())
                ->firstOrFail();

            $lockedIssue = Issue::query()
                ->whereKey($assignmentSnapshot->issue_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAssignment = Assignment::query()
                ->whereKey($assignmentSnapshot->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAssignment->released_at !== null) {
                throw new DomainException('A released assignment cannot be accepted.');
            }

            if ($lockedAssignment->accepted_at !== null) {
                throw new DomainException('This assignment has already been accepted.');
            }

            Gate::forUser($technician)->authorize(
                'acceptAssignment',
                [$lockedIssue, $lockedAssignment],
            );

            $lockedAssignment->forceFill(['accepted_at' => now()])->saveOrFail();

            return $lockedAssignment;
        });
    }

    public function start(Issue $issue, User $technician): StatusHistory
    {
        return $this->transition($issue, IssueStatus::InProgress, $technician);
    }

    public function putOnHold(Issue $issue, User $technician, string $reason): StatusHistory
    {
        return $this->transition($issue, IssueStatus::OnHold, $technician, $reason);
    }

    public function updateProgress(Issue $issue, User $technician, string $body): int
    {
        $body = trim($body);

        if ($body === '') {
            throw new InvalidArgumentException('A progress update cannot be empty.');
        }

        return DB::transaction(function () use ($issue, $technician, $body): int {
            $lockedIssue = Issue::query()
                ->whereKey($issue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($technician)->authorize('addProgressUpdate', $lockedIssue);

            $now = now();

            return (int) DB::table('comments')->insertGetId([
                'issue_id' => $lockedIssue->getKey(),
                'user_id' => $technician->getKey(),
                'body' => $body,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function resolve(
        Issue $issue,
        User $technician,
        string $rootCause,
        string $actionTaken,
        ?string $partsUsed,
        int $minutesSpent,
    ): Resolution {
        $rootCause = trim($rootCause);
        $actionTaken = trim($actionTaken);
        $partsUsed = $this->normalizeReason($partsUsed);

        if ($rootCause === '' || $actionTaken === '') {
            throw new InvalidArgumentException('Root cause and action taken are required to resolve an issue.');
        }

        if ($minutesSpent < 0) {
            throw new InvalidArgumentException('Minutes spent cannot be negative.');
        }

        return DB::transaction(function () use (
            $issue,
            $technician,
            $rootCause,
            $actionTaken,
            $partsUsed,
            $minutesSpent,
        ): Resolution {
            $lockedIssue = Issue::query()
                ->whereKey($issue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $from = $this->statusOf($lockedIssue);

            if (! $from->canTransitionTo(IssueStatus::Resolved)) {
                throw new InvalidIssueTransition($from, IssueStatus::Resolved);
            }

            Gate::forUser($technician)->authorize(
                'transitionStatus',
                [$lockedIssue, IssueStatus::Resolved],
            );

            $assignment = Assignment::query()
                ->where('issue_id', $lockedIssue->getKey())
                ->where('technician_id', $technician->getKey())
                ->whereNotNull('accepted_at')
                ->whereNull('released_at')
                ->lockForUpdate()
                ->firstOrFail();

            $resolution = Resolution::query()->create([
                'issue_id' => $lockedIssue->getKey(),
                'assignment_id' => $assignment->getKey(),
                'resolved_by' => $technician->getKey(),
                'root_cause' => $rootCause,
                'action_taken' => $actionTaken,
                'parts_used' => $partsUsed,
                'minutes_spent' => $minutesSpent,
            ]);

            $this->transition($lockedIssue, IssueStatus::Resolved, $technician);

            $assignment->forceFill(['released_at' => now()])->saveOrFail();

            return $resolution;
        });
    }

    public function close(
        Issue $issue,
        User $reporter,
        ?int $rating = null,
        ?string $comment = null,
    ): StatusHistory
    {
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new InvalidArgumentException('Feedback rating must be between 1 and 5.');
        }

        $comment = $this->normalizeReason($comment);

        return DB::transaction(function () use ($issue, $reporter, $rating, $comment): StatusHistory {
            $history = $this->transition($issue, IssueStatus::Closed, $reporter);

            if ($rating !== null) {
                Feedback::query()->firstOrCreate(
                    ['issue_id' => $issue->getKey(), 'user_id' => $reporter->getKey()],
                    ['rating' => $rating, 'comment' => $comment],
                );
            }

            return $history;
        });
    }

    public function reopen(Issue $issue, User $reporter, string $reason): StatusHistory
    {
        return $this->transition($issue, IssueStatus::Reopened, $reporter, $reason);
    }

    public function closeAsGuest(Issue $issue, GuestIssueReporter $reporter): StatusHistory
    {
        return $this->transitionAsGuestReporter(
            $issue,
            $reporter,
            IssueStatus::Closed,
            'Confirmed by verified guest reporter.',
        );
    }

    public function reopenAsGuest(
        Issue $issue,
        GuestIssueReporter $reporter,
        string $reason,
    ): StatusHistory {
        return $this->transitionAsGuestReporter($issue, $reporter, IssueStatus::Reopened, $reason);
    }

    public function transition(
        Issue $issue,
        IssueStatus $target,
        User $actor,
        ?string $reason = null,
    ): StatusHistory {
        if ($target === IssueStatus::Merged) {
            throw new DomainException('Use the merge workflow so the canonical issue is recorded.');
        }

        return $this->transitionWithAttributes($issue, $target, $actor, $reason, []);
    }

    /** @param array<string, mixed> $attributes */
    private function transitionWithAttributes(
        Issue $issue,
        IssueStatus $target,
        User $actor,
        ?string $reason,
        array $attributes,
    ): StatusHistory {
        $reason = $this->normalizeReason($reason);
        $this->assertRequiredReason($target, $reason);

        return DB::transaction(function () use ($issue, $target, $actor, $reason, $attributes): StatusHistory {
            $lockedIssue = Issue::query()
                ->whereKey($issue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $from = $this->statusOf($lockedIssue);

            if (! $from->canTransitionTo($target)) {
                throw new InvalidIssueTransition($from, $target);
            }

            Gate::forUser($actor)->authorize('transitionStatus', [$lockedIssue, $target]);

            $lockedIssue->forceFill(['status' => $target->value] + $attributes)->saveOrFail();

            $history = StatusHistory::query()->create([
                'issue_id' => $lockedIssue->getKey(),
                'from_status' => $from->value,
                'to_status' => $target->value,
                'actor_id' => $actor->getKey(),
                'reason' => $reason,
            ]);

            $this->dispatchAfterCommit($history);

            return $history;
        });
    }

    private function transitionAsGuestReporter(
        Issue $issue,
        GuestIssueReporter $reporter,
        IssueStatus $target,
        ?string $reason,
    ): StatusHistory {
        if (! in_array($target, [IssueStatus::Closed, IssueStatus::Reopened], true)) {
            throw new LogicException('Guest reporters may only close or reopen their own issues.');
        }

        $reason = $this->normalizeReason($reason);
        $this->assertRequiredReason($target, $reason);

        return DB::transaction(function () use ($issue, $reporter, $target, $reason): StatusHistory {
            $lockedIssue = Issue::query()
                ->whereKey($issue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $canAct = GuestIssueReporter::query()
                ->whereKey($reporter->getKey())
                ->where('issue_id', $lockedIssue->getKey())
                ->whereNotNull('email_verified_at')
                ->exists();

            if (! $canAct) {
                throw new AuthorizationException('This verified guest reporter cannot update the issue.');
            }

            $from = $this->statusOf($lockedIssue);

            if (! $from->canTransitionTo($target)) {
                throw new InvalidIssueTransition($from, $target);
            }

            $lockedIssue->forceFill(['status' => $target->value])->saveOrFail();

            $history = StatusHistory::query()->create([
                'issue_id' => $lockedIssue->getKey(),
                'from_status' => $from->value,
                'to_status' => $target->value,
                'actor_id' => null,
                'reason' => $reason,
            ]);

            $this->dispatchAfterCommit($history);

            return $history;
        });
    }

    private function statusOf(Issue $issue): IssueStatus
    {
        $rawStatus = $issue->getRawOriginal('status') ?? $issue->getAttribute('status');

        if ($rawStatus instanceof IssueStatus) {
            return $rawStatus;
        }

        return IssueStatus::tryFrom((string) $rawStatus)
            ?? throw new UnexpectedValueException(sprintf(
                'Issue %s has an unknown status "%s".',
                $issue->getKey(),
                (string) $rawStatus,
            ));
    }

    private function normalizeLocationDetail(?string $detail): string
    {
        return Str::lower(trim($detail ?? ''));
    }

    private function normalizeReason(?string $reason): ?string
    {
        $reason = $reason === null ? null : trim($reason);

        return $reason === '' ? null : $reason;
    }

    private function assertRequiredReason(IssueStatus $target, ?string $reason): void
    {
        if (
            in_array($target, [IssueStatus::Rejected, IssueStatus::OnHold, IssueStatus::Reopened, IssueStatus::Merged], true)
            && $reason === null
        ) {
            throw new DomainException(sprintf(
                'A reason is required when moving an issue to "%s".',
                $target->value,
            ));
        }
    }

    private function dispatchAfterCommit(StatusHistory $history): void
    {
        DB::afterCommit(static function () use ($history): void {
            event(new IssueStatusChanged($history));
        });
    }
}
