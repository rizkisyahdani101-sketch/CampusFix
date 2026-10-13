<?php

declare(strict_types=1);

namespace App\Livewire\Technician;

use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use App\Services\IssueWorkflow;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class IssueQueue extends Component
{
    use WithPagination;

    public array $progressUpdates = [];

    public array $holdReasons = [];

    public array $resolutionForms = [];

    public function accept(int $assignmentId, IssueWorkflow $workflow): void
    {
        $technician = $this->technician();
        $assignment = Assignment::query()->findOrFail($assignmentId);

        $workflow->acceptAssignment($assignment, $technician);

        session()->flash('workflow-status', 'Assignment accepted.');
    }

    public function startWork(int $issueId, IssueWorkflow $workflow): void
    {
        $workflow->start(Issue::query()->findOrFail($issueId), $this->technician());

        session()->flash('workflow-status', 'Work started.');
    }

    public function updateProgress(int $issueId, IssueWorkflow $workflow): void
    {
        $technician = $this->technician();

        $this->validate([
            "progressUpdates.{$issueId}" => ['required', 'string', 'max:5000'],
        ]);

        $workflow->updateProgress(
            Issue::query()->findOrFail($issueId),
            $technician,
            $this->progressUpdates[$issueId],
        );

        unset($this->progressUpdates[$issueId]);
        session()->flash('workflow-status', 'Progress update saved.');
    }

    public function hold(int $issueId, IssueWorkflow $workflow): void
    {
        $technician = $this->technician();

        $this->validate([
            "holdReasons.{$issueId}" => ['required', 'string', 'max:2000'],
        ]);

        $workflow->putOnHold(
            Issue::query()->findOrFail($issueId),
            $technician,
            $this->holdReasons[$issueId],
        );

        unset($this->holdReasons[$issueId]);
        session()->flash('workflow-status', 'Issue put on hold.');
    }

    public function resolve(int $issueId, IssueWorkflow $workflow): void
    {
        $technician = $this->technician();

        $this->validate([
            "resolutionForms.{$issueId}.root_cause" => ['required', 'string', 'max:5000'],
            "resolutionForms.{$issueId}.action_taken" => ['required', 'string', 'max:5000'],
            "resolutionForms.{$issueId}.parts_used" => ['nullable', 'string', 'max:5000'],
            "resolutionForms.{$issueId}.minutes_spent" => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $form = $this->resolutionForms[$issueId];

        $workflow->resolve(
            Issue::query()->findOrFail($issueId),
            $technician,
            $form['root_cause'],
            $form['action_taken'],
            $form['parts_used'] ?? null,
            (int) $form['minutes_spent'],
        );

        unset($this->resolutionForms[$issueId]);
        session()->flash('workflow-status', 'Resolution submitted.');
    }

    public function render(): View
    {
        $technician = $this->technician();

        $assignments = Assignment::query()
            ->with('issue.location.parent.parent.parent.parent')
            ->where('technician_id', $technician->getKey())
            ->whereNull('released_at')
            ->orderByDesc('assigned_at')
            ->paginate(20);

        $issueIds = $assignments->getCollection()->pluck('issue_id')->all();
        $comments = $issueIds === []
            ? collect()
            : Comment::query()
                ->whereIn('issue_id', $issueIds)
                ->orderByDesc('created_at')
                ->limit(100)
                ->get()
                ->groupBy('issue_id');

        return view('livewire.technician.issue-queue', [
            'assignments' => $assignments,
            'commentsByIssue' => $comments,
        ]);
    }

    private function technician(): User
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User && (string) $actor->role === 'technician', 403);

        return $actor;
    }
}
