<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PendingGuestReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class PrunePendingGuestReports extends Command
{
    protected $signature = 'campusfix:prune-pending-guest-reports';

    protected $description = 'Remove expired guest reports awaiting email verification.';

    public function handle(): int
    {
        PendingGuestReport::query()
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, static function ($reports): void {
                foreach ($reports as $report) {
                    if ($report->photo_path !== null) {
                        Storage::disk('local')->delete($report->photo_path);
                    }

                    $report->delete();
                }
            });

        $this->info('Expired guest reports were removed.');

        return self::SUCCESS;
    }
}
