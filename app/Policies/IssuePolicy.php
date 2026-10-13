<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\IssueStatus;
use App\Models\Assignment;
use App\Models\Issue;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class IssuePolicy
{
    public function view(User $actor, Issue $issue): bool
    {
        if (in_array((string) $actor->role, ['coordinator', 'admin', 'super_admin'], true)) {
            return true;
        }

        if ((string) $actor->role === 'member') {
            return true;
        }

        if ((string) $actor->role === 'technician') {
            return DB::table('assignments')
                ->where('issue_id', $issue->getKey())
                ->where('technician_id', $actor->getKey())
                ->exists();
        }

        return DB::table('issue_user')
            ->where('issue_id', $issue->getKey())
            ->where('user_id', $actor->getKey())
            ->exists();
    }

    public function reportMeToo(User $actor, Issue $issue): bool
    {
        return (string) $actor->role === 'member'
            && in_array($this->statusOf($issue), [
                IssueStatus::Reported,
                IssueStatus::Verified,
                IssueStatus::Assigned,
                IssueStatus::InProgress,
                IssueStatus::OnHold,
                IssueStatus::Reopened,
            ], true);
    }

    public function recordInitialReported(User $actor, Issue $issue): bool
    {
        return (string) $actor->role === 'member'
            && $this->statusOf($issue) === IssueStatus::Reported
            && $this->isReporter($actor, $issue);
    }

    public function assignIssue(
        User $actor,
        Issue $issue,
        Team $team,
        User $technician,
    ): bool {
        if (
            ! $this->isCoordinator($actor)
            || (string) $technician->role !== 'technician'
            || ! in_array($this->statusOf($issue), [IssueStatus::Verified, IssueStatus::Assigned], true)
        ) {
            return false;
        }

        return DB::table('team_user')
            ->where('team_id', $team->getKey())
            ->where('user_id', $technician->getKey())
            ->exists();
    }

    public function acceptAssignment(
        User $actor,
        Issue $issue,
        Assignment $assignment,
    ): bool {
        return (string) $actor->role === 'technician'
            && (string) $assignment->issue_id === (string) $issue->getKey()
            && (string) $assignment->technician_id === (string) $actor->getKey()
            && $assignment->accepted_at === null
            && $assignment->released_at === null
            && $this->statusOf($issue) === IssueStatus::Assigned;
    }

    public function addProgressUpdate(User $actor, Issue $issue): bool
    {
        return in_array($this->statusOf($issue), [IssueStatus::InProgress, IssueStatus::OnHold], true)
            && $this->isAcceptedTechnician($actor, $issue);
    }

    public function transitionStatus(User $actor, Issue $issue, IssueStatus $target): bool
    {
        $from = $this->statusOf($issue);

        if ($from === null || ! $from->canTransitionTo($target)) {
            return false;
        }

        return match ($from->value.'->'.$target->value) {
            'reported->verified',
            'reported->rejected',
            'verified->assigned',
            'verified->rejected',
            'reopened->verified',
            'reported->merged',
            'verified->merged',
            'assigned->merged',
            'reopened->merged' => $this->isCoordinator($actor),

            'assigned->in_progress',
            'in_progress->on_hold',
            'in_progress->resolved',
            'on_hold->in_progress' => $this->isAcceptedTechnician($actor, $issue),

            'resolved->closed',
            'resolved->reopened',
            'closed->reopened' => $this->isReporter($actor, $issue),

            default => false,
        };
    }

    private function isCoordinator(User $actor): bool
    {
        return in_array((string) $actor->role, ['coordinator', 'admin', 'super_admin'], true);
    }

    private function isAcceptedTechnician(User $actor, Issue $issue): bool
    {
        if ((string) $actor->role !== 'technician') {
            return false;
        }

        return DB::table('assignments')
            ->where('issue_id', $issue->getKey())
            ->where('technician_id', $actor->getKey())
            ->whereNotNull('accepted_at')
            ->whereNull('released_at')
            ->exists();
    }

    private function isReporter(User $actor, Issue $issue): bool
    {
        // The Issue model owns this relation because issue_user is shared with reporting.
        if (! method_exists($issue, 'reporters')) {
            return false;
        }

        return $issue->reporters()
            ->whereKey($actor->getKey())
            ->exists();
    }

    private function statusOf(Issue $issue): ?IssueStatus
    {
        $rawStatus = $issue->getRawOriginal('status') ?? $issue->getAttribute('status');

        return $rawStatus instanceof IssueStatus
            ? $rawStatus
            : IssueStatus::tryFrom((string) $rawStatus);
    }
}
