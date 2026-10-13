<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

final class NotificationController extends Controller
{
    public function markRead(Request $request, string $notification): RedirectResponse
    {
        /** @var DatabaseNotification $notificationRecord */
        $notificationRecord = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $notificationRecord->markAsRead();

        $issueId = $notificationRecord->data['issue_id'] ?? null;

        return $issueId
            ? redirect()->route('issues.show', $issueId)
            : redirect()->route('dashboard');
    }
}
