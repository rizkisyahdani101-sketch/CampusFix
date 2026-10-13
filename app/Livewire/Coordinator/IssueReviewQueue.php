<?php

declare(strict_types=1);

namespace App\Livewire\Coordinator;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\User;
use App\Services\IssueWorkflow;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class IssueReviewQueue extends Component
{
    use WithPagination;

    public array $rejectionReasons = [];

    public array $priorities = [];

    public array $duplicateTargets = [];

    public function verify(int $issueId, IssueWorkflow $workflow): void
    {
        $coordinator = $this->coordinator();
        $this->validate([
            "priorities.{$issueId}" => ['nullable', 'in:low,medium,high,critical'],
        ]);
        $issue = Issue::query()->findOrFail($issueId);

        $workflow->verify($issue, $coordinator, priority: $this->priorities[$issueId] ?? 'medium');

        unset($this->priorities[$issueId]);
        session()->flash('workflow-status', 'Issue verified.');
        $this->resetPage();
    }

    public function mergeDuplicate(int $issueId, IssueWorkflow $workflow): void
    {
        $coordinator = $this->coordinator();
        $this->validate([
            "duplicateTargets.{$issueId}" => ['required', 'integer', 'exists:issues,id'],
        ]);

        $duplicate = Issue::query()
            ->whereIn('status', [IssueStatus::Reported->value, IssueStatus::Reopened->value])
            ->findOrFail($issueId);
        $canonical = Issue::query()->findOrFail((int) $this->duplicateTargets[$issueId]);

        $workflow->merge($duplicate, $canonical, $coordinator);

        unset($this->duplicateTargets[$issueId]);
        session()->flash('workflow-status', 'Duplicate merged into issue #'.$canonical->getKey().'.');
        $this->resetPage();
    }

    public function reject(int $issueId, IssueWorkflow $workflow): void
    {
        $coordinator = $this->coordinator();

        $this->validate([
            "rejectionReasons.{$issueId}" => ['required', 'string', 'max:2000'],
        ]);

        $issue = Issue::query()->findOrFail($issueId);
        $workflow->reject($issue, $coordinator, $this->rejectionReasons[$issueId]);

        unset($this->rejectionReasons[$issueId]);
        session()->flash('workflow-status', 'Issue rejected.');
        $this->resetPage();
    }

    public function render(): View
    {
        $this->coordinator();

        return view('livewire.coordinator.issue-review-queue', [
            'issues' => Issue::query()
                ->with('location.parent.parent.parent.parent')
                ->whereIn('status', [IssueStatus::Reported->value, IssueStatus::Reopened->value])
                ->orderBy('created_at')
                ->paginate(20),
            'mergeCandidates' => Issue::query()
                ->whereNotIn('status', [IssueStatus::Rejected->value, IssueStatus::Merged->value])
                ->whereNull('merged_into_issue_id')
                ->orderByDesc('created_at')
                ->limit(100)
                ->get(['id', 'title', 'status', 'category_id', 'location_id', 'location_detail']),
        ]);
    }

    private function coordinator(): User
    {
        $actor = auth()->user();

        abort_unless(
            $actor instanceof User
            && in_array((string) $actor->role, ['coordinator', 'admin', 'super_admin'], true),
            403,
        );

        return $actor;
    }
}
