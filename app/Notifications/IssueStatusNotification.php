<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\StatusHistory;
use Illuminate\Notifications\Notification;

final class IssueStatusNotification extends Notification
{
    public function __construct(public StatusHistory $history)
    {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $issue = $this->history->issue;
        $isNewReport = $this->history->from_status === null;

        return [
            'event' => 'issue_status_changed',
            'is_new_report' => $isNewReport,
            'issue_id' => $this->history->issue_id,
            'issue_title' => $issue?->title,
            'from_status' => $this->history->from_status,
            'to_status' => $this->history->to_status,
            'message' => $this->messageLabel($isNewReport),
            'actor' => $this->history->actor?->name,
            'reason' => $this->history->reason,
            'url' => $issue ? route('issues.show', $issue) : null,
        ];
    }

    private function messageLabel(bool $isNewReport): string
    {
        if ($isNewReport) {
            return 'Laporan baru menunggu tindak lanjut';
        }

        return sprintf(
            'Status %s → %s',
            $this->humanStatus($this->history->from_status),
            $this->humanStatus($this->history->to_status),
        );
    }

    private function humanStatus(?string $status): string
    {
        $status = (string) $status;

        return $status === '' ? 'Baru' : (string) str($status)->replace('_', ' ')->title();
    }
}
