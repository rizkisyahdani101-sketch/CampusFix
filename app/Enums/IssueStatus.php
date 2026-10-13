<?php

declare(strict_types=1);

namespace App\Enums;

enum IssueStatus: string
{
    case Reported = 'reported';
    case Verified = 'verified';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case OnHold = 'on_hold';
    case Reopened = 'reopened';
    case Merged = 'merged';

    /**
     * Allowed edges in the MVP workflow.
     *
     * Merged issues point to a canonical issue and remain terminal.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'reported' => ['verified', 'rejected', 'merged'],
        'verified' => ['assigned', 'rejected', 'merged'],
        'assigned' => ['in_progress', 'merged'],
        'in_progress' => ['resolved', 'on_hold'],
        'on_hold' => ['in_progress'],
        'resolved' => ['closed', 'reopened'],
        'closed' => ['reopened'],
        'reopened' => ['verified', 'merged'],
        'rejected' => [],
        'merged' => [],
    ];

    public function canTransitionTo(self $next): bool
    {
        return in_array($next->value, self::TRANSITIONS[$this->value], true);
    }
}
