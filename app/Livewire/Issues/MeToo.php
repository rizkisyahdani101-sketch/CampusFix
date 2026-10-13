<?php

declare(strict_types=1);

namespace App\Livewire\Issues;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class MeToo extends Component
{
    public Issue $issue;

    public function mount(Issue $issue): void
    {
        Gate::authorize('view', $issue);
        $this->issue = $issue;
    }

    public function confirmImpact(): void
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 403);
        Gate::forUser($actor)->authorize('reportMeToo', $this->issue);

        $this->issue->affectedUsers()->syncWithoutDetaching([
            $actor->getKey() => ['relationship' => 'affected'],
        ]);

        session()->flash('me-too-status', 'Terima kasih, dampak issue ini sudah dicatat.');
    }

    public function render()
    {
        $actor = auth()->user();
        $hasConfirmed = $actor instanceof User
            && $this->issue->affectedUsers()->whereKey($actor->getKey())->exists();

        return view('livewire.issues.me-too', [
            'hasConfirmed' => $hasConfirmed,
            'affectedCount' => $this->issue->affectedUsers()->count(),
            'canConfirm' => $actor instanceof User
                && Gate::forUser($actor)->allows('reportMeToo', $this->issue),
            'isMerged' => $this->issue->status === IssueStatus::Merged->value,
        ]);
    }
}
