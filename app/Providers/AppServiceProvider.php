<?php
namespace App\Providers;

use App\Events\IssueStatusChanged;
use App\Services\NotifyIssueParticipants;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Event::listen(IssueStatusChanged::class, NotifyIssueParticipants::class);
    }
}
