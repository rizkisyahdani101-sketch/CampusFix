<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\GuestIssueReporter;
use App\Models\StatusHistory;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class GuestIssueStatusUpdate extends Notification
{
    public function __construct(public StatusHistory $history)
    {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $issue = $this->history->issue;
        $guestReporter = GuestIssueReporter::query()
            ->where('issue_id', $this->history->issue_id)
            ->first();

        $url = $guestReporter
            ? URL::signedRoute('guest.issues.show', ['guestIssueReporter' => $guestReporter])
            : route('guest.issues.create');

        $mail = (new MailMessage)
            ->subject('Pembaruan laporan CampusFix UNIMED #'.$this->history->issue_id)
            ->greeting('Pembaruan laporan CampusFix UNIMED')
            ->line('Status laporan “'.($issue?->title ?? 'Issue #'.$this->history->issue_id).'” sekarang: '.str($this->history->to_status)->replace('_', ' ')->title().'.');

        if ($this->history->reason !== null) {
            $mail->line('Catatan: '.$this->history->reason);
        }

        return $mail->action('Lihat laporan', $url);
    }
}
