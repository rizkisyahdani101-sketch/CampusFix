<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\IssueStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IssueStatusTest extends TestCase
{
    #[DataProvider('allowedTransitions')]
    public function test_allowed_transitions_are_accepted(
        IssueStatus $from,
        IssueStatus $to,
    ): void {
        self::assertTrue($from->canTransitionTo($to));
    }

    public static function allowedTransitions(): iterable
    {
        yield 'reported to verified' => [IssueStatus::Reported, IssueStatus::Verified];
        yield 'reported to rejected' => [IssueStatus::Reported, IssueStatus::Rejected];
        yield 'verified to assigned' => [IssueStatus::Verified, IssueStatus::Assigned];
        yield 'verified to rejected' => [IssueStatus::Verified, IssueStatus::Rejected];
        yield 'assigned to in progress' => [IssueStatus::Assigned, IssueStatus::InProgress];
        yield 'in progress to on hold' => [IssueStatus::InProgress, IssueStatus::OnHold];
        yield 'in progress to resolved' => [IssueStatus::InProgress, IssueStatus::Resolved];
        yield 'on hold to in progress' => [IssueStatus::OnHold, IssueStatus::InProgress];
        yield 'resolved to closed' => [IssueStatus::Resolved, IssueStatus::Closed];
        yield 'resolved to reopened' => [IssueStatus::Resolved, IssueStatus::Reopened];
        yield 'closed to reopened' => [IssueStatus::Closed, IssueStatus::Reopened];
        yield 'reopened to verified' => [IssueStatus::Reopened, IssueStatus::Verified];
    }

    public function test_reported_issue_cannot_skip_verification_and_close(): void
    {
        self::assertFalse(
            IssueStatus::Reported->canTransitionTo(IssueStatus::Closed),
        );
    }

    public function test_rejected_and_merged_are_terminal(): void
    {
        foreach (IssueStatus::cases() as $next) {
            self::assertFalse(IssueStatus::Rejected->canTransitionTo($next));
            self::assertFalse(IssueStatus::Merged->canTransitionTo($next));
        }
    }
}
