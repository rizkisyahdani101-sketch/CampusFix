<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\IssueStatus;
use App\Models\Feedback;
use App\Models\Issue;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $route = match ($request->user()->role) {
            User::ROLE_SUPER_ADMIN => 'superadmin.dashboard',
            User::ROLE_ADMIN => 'admin.dashboard',
            User::ROLE_COORDINATOR => 'coordinator.dashboard',
            User::ROLE_TECHNICIAN => 'technician.dashboard',
            default => 'member.dashboard',
        };

        return redirect()->route($route);
    }

    public function member(Request $request): View
    {
        return $this->dashboard($request, 'member');
    }

    public function technician(Request $request): View
    {
        return $this->dashboard($request, 'technician');
    }

    public function coordinator(Request $request): View
    {
        return $this->dashboard($request, 'coordinator');
    }

    public function admin(Request $request): View
    {
        return $this->dashboard($request, 'admin');
    }

    public function superadmin(Request $request): View
    {
        return $this->dashboard($request, 'super_admin');
    }

    private function dashboard(Request $request, string $area): View
    {
        /** @var User $user */
        $user = $request->user();
        $issues = Issue::query()->with(['category', 'location.parent.parent.parent.parent']);

        if ($area === 'member') {
            $issues->whereHas('reporters', fn ($query) => $query->whereKey($user->getKey()));
        } elseif ($area === 'technician') {
            $issues->whereHas('assignments', fn ($query) => $query
                ->where('technician_id', $user->getKey())
                ->whereNull('released_at'));
        }

        $statusCounts = $issues->clone()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $counts = array_fill_keys(
            array_map(static fn (IssueStatus $status): string => $status->value, IssueStatus::cases()),
            0,
        );
        $counts = array_replace($counts, array_map('intval', $statusCounts));

        $links = match ($area) {
            'member' => [
                ['label' => 'Buat laporan', 'route' => 'issues.create'],
                ['label' => 'Konfirmasi / buka kembali', 'route' => 'reporter.issues.confirmation'],
            ],
            'technician' => [
                ['label' => 'Antrean pekerjaan', 'route' => 'technician.issues.index'],
            ],
            'coordinator' => [
                ['label' => 'Verifikasi laporan', 'route' => 'coordinator.issues.review'],
                ['label' => 'Penugasan teknisi', 'route' => 'coordinator.issues.assign'],
            ],
            'admin' => [
                ['label' => 'Kelola pengguna', 'route' => 'admin.users.index'],
            ],
            default => [
                ['label' => 'Kelola pengguna', 'route' => 'admin.users.index'],
                ['label' => 'Daftar role', 'route' => 'superadmin.roles.index'],
            ],
        };

        return view('dashboards.index', [
            'title' => $area === 'member' ? 'Beranda mahasiswa' : 'Dashboard '.($area === 'super_admin' ? 'Super Admin' : ucfirst($area)),
            'user' => $user,
            'counts' => $counts,
            'totalIssues' => array_sum($counts),
            'activeIssues' => $counts['reported'] + $counts['verified'] + $counts['assigned']
                + $counts['in_progress'] + $counts['on_hold'] + $counts['reopened'],
            'recentIssues' => $issues->latest()->limit(8)->get(),
            'categories' => $area === 'member'
                ? Category::query()->orderForReporting()->get()
                : collect(),
            'links' => $links,
            'userCount' => in_array($area, ['admin', 'super_admin'], true) ? User::query()->count() : null,
            'notifications' => $user->notifications()->latest()->limit(8)->get(),
            'unreadNotificationCount' => $user->unreadNotifications()->count(),
            'averageFeedback' => in_array($area, ['coordinator', 'admin', 'super_admin'], true)
                ? Feedback::query()->avg('rating')
                : null,
        ]);
    }
}
