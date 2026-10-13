<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\StatusHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class IssueStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly StatusHistory $history)
    {
    }
}
