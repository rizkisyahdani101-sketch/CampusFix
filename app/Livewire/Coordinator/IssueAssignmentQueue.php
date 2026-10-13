<?php

declare(strict_types=1);

namespace App\Livewire\Coordinator;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Team;
use App\Models\User;
use App\Services\IssueWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

final class IssueAssignmentQueue extends Component
{
    use WithPagination;

    public array $teamIds = [];

    public array $technicianIds = [];

    public function updatedTeamIds(mixed $teamId, string $issueId): void
    {
        unset($this->technicianIds[$issueId]);
        $this->resetValidation("technicianIds.{$issueId}");
    }

    public function assign(int $issueId, IssueWorkflow $workflow): void
    {
        $coordinator = $this->coordinator();

        $this->validate([
            "teamIds.{$issueId}" => ['required', 'integer', 'exists:teams,id'],
            "technicianIds.{$issueId}" => ['required', 'integer', 'exists:users,id'],
        ]);

        $issue = Issue::query()
            ->whereIn('status', [IssueStatus::Verified->value, IssueStatus::Assigned->value])
            ->findOrFail($issueId);

        $team = Team::query()->findOrFail((int) $this->teamIds[$issueId]);
        $technician = User::query()
            ->where('role', 'technician')
            ->findOrFail((int) $this->technicianIds[$issueId]);

        $isTeamMember = DB::table('team_user')
            ->where('team_id', $team->getKey())
            ->where('user_id', $technician->getKey())
            ->exists();

        if (! $isTeamMember) {
            $this->addError(
                "technicianIds.{$issueId}",
                'Pilih teknisi yang menjadi anggota tim tersebut.',
            );

            return;
        }

        $workflow->assign($issue, $team, $technician, $coordinator);

        unset($this->teamIds[$issueId], $this->technicianIds[$issueId]);
        session()->flash('workflow-status', 'Technician assigned.');
        $this->resetPage();
    }

    public function render(): View
    {
        $this->coordinator();

        return view('livewire.coordinator.issue-assignment-queue', [
            'issues' => Issue::query()
                ->with('location.parent.parent.parent.parent')
                ->whereIn('status', [IssueStatus::Verified->value, IssueStatus::Assigned->value])
                ->orderBy('updated_at')
                ->paginate(20),
            'teams' => Team::query()->orderBy('name')->get(),
            'technicians' => User::query()
                ->where('role', 'technician')
                ->with('teams')
                ->orderBy('name')
                ->get(),
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
