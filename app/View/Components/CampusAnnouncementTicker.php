<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\CampusAnnouncement;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class CampusAnnouncementTicker extends Component
{
    public function render(): View
    {
        $messages = CampusAnnouncement::query()
            ->where('is_active', true)
            ->latest()
            ->limit(10)
            ->pluck('message');

        return view('components.campus-announcement-ticker', [
            'tickerText' => $messages->implode('  ·  '),
        ]);
    }
}
