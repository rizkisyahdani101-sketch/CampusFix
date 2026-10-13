<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Issue;
use App\Models\Location;
use App\Models\User;
use App\Notifications\IssueStatusNotification;
use App\Services\IssueWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class IssueNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_report_notifies_every_role_except_member(): void
    {
        Notification::fake();

        $reporter = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $technician = User::factory()->create(['role' => User::ROLE_TECHNICIAN]);
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $bystander = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $issue = $this->reportedIssue($reporter, 'AC ruang 204 mati');

        app(IssueWorkflow::class)->recordInitialReported($issue, $reporter);

        foreach ([$technician, $coordinator, $admin, $superAdmin] as $staff) {
            Notification::assertSentTo($staff, IssueStatusNotification::class);
        }

        Notification::assertSentTo(
            $coordinator,
            IssueStatusNotification::class,
            function (IssueStatusNotification $notification) use ($coordinator, $issue): bool {
                $data = $notification->toDatabase($coordinator);

                return $data['is_new_report'] === true
                    && (int) $data['issue_id'] === $issue->getKey()
                    && $data['message'] === 'Laporan baru menunggu tindak lanjut';
            },
        );

        Notification::assertNotSentTo($reporter, IssueStatusNotification::class);
        Notification::assertNotSentTo($bystander, IssueStatusNotification::class);
    }

    public function test_a_later_status_change_does_not_notify_every_staff_member(): void
    {
        $reporter = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $unassignedTechnician = User::factory()->create(['role' => User::ROLE_TECHNICIAN]);

        $issue = $this->reportedIssue($reporter, 'Lampu koridor mati');
        app(IssueWorkflow::class)->recordInitialReported($issue, $reporter);

        Notification::fake();

        app(IssueWorkflow::class)->verify($issue, $coordinator);

        Notification::assertSentTo($reporter, IssueStatusNotification::class);
        Notification::assertSentTo(
            $reporter,
            IssueStatusNotification::class,
            function (IssueStatusNotification $notification) use ($reporter): bool {
                $data = $notification->toDatabase($reporter);

                return $data['is_new_report'] === false
                    && $data['message'] === 'Status Reported → Verified';
            },
        );
        Notification::assertNotSentTo($coordinator, IssueStatusNotification::class);
        Notification::assertNotSentTo($unassignedTechnician, IssueStatusNotification::class);
    }

    public function test_new_report_notifications_reach_the_database_without_a_queue_worker(): void
    {
        // Reproduce the local setup: the database queue with no worker running.
        config(['queue.default' => 'database']);

        $reporter = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $technician = User::factory()->create(['role' => User::ROLE_TECHNICIAN]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $issue = $this->reportedIssue($reporter, 'Proyektor ruang F101 rusak');

        app(IssueWorkflow::class)->recordInitialReported($issue, $reporter);

        $this->assertDatabaseCount('jobs', 0);

        foreach ([$technician, $admin] as $staff) {
            $this->assertSame(1, $staff->unreadNotifications()->count());
            $this->assertSame(
                'Laporan baru menunggu tindak lanjut',
                $staff->notifications()->first()->data['message'],
            );
        }

        $this->assertSame(0, $reporter->notifications()->count());
    }

    private function reportedIssue(User $reporter, string $title): Issue
    {
        $category = Category::query()->create(['name' => 'Fasilitas umum']);
        $location = Location::query()->create(['name' => 'Gedung A', 'type' => 'building']);

        $issue = Issue::query()->create([
            'title' => $title,
            'description' => 'Kendala ini menghambat kegiatan belajar di ruang tersebut.',
            'category_id' => $category->getKey(),
            'location_id' => $location->getKey(),
            'status' => 'reported',
        ]);

        $issue->reporters()->attach($reporter->getKey(), ['relationship' => 'reporter']);

        return $issue;
    }
}
