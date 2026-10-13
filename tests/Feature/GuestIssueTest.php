<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Models\Category;
use App\Models\GuestIssueReporter;
use App\Models\Issue;
use App\Models\Location;
use App\Models\PendingGuestReport;
use App\Notifications\GuestReportVerification;
use App\Services\IssueWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

final class GuestIssueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_visitor_can_open_the_guest_report_form(): void
    {
        $this->get(route('guest.issues.create'))
            ->assertOk()
            ->assertSee('Laporkan masalah')
            ->assertSee('Kategori masalah');
    }

    public function test_guest_issue_is_created_only_after_email_verification(): void
    {
        Notification::fake();
        [$category, $room] = $this->reportingOptions();

        $this->post(route('guest.issues.store'), [
            'name' => 'Rizki Reporter',
            'email' => 'RIZKI@EXAMPLE.EDU',
            'title' => 'Wi-Fi ruang 101 mati',
            'description' => 'Wi-Fi tidak berfungsi sejak pagi tadi.',
            'category_id' => $category->getKey(),
            'location_id' => $room->getKey(),
        ])->assertRedirect(route('guest.issues.create'));

        $this->assertDatabaseCount('issues', 0);
        $this->assertDatabaseHas('pending_guest_reports', ['email' => 'rizki@example.edu']);

        $verificationUrl = null;
        Notification::assertSentOnDemand(
            GuestReportVerification::class,
            function (GuestReportVerification $notification) use (&$verificationUrl): bool {
                $verificationUrl = $notification->verificationUrl;

                return true;
            },
        );

        $this->assertNotNull($verificationUrl);
        $this->get($verificationUrl)->assertRedirect();

        $this->assertDatabaseCount('issues', 1);
        $this->assertDatabaseCount('guest_issue_reporters', 1);
        $this->assertDatabaseCount('status_histories', 1);
        $this->assertDatabaseCount('pending_guest_reports', 0);
        $this->assertDatabaseHas('status_histories', [
            'from_status' => null,
            'to_status' => IssueStatus::Reported->value,
            'actor_id' => null,
            'reason' => 'Guest reporter email verified.',
        ]);
    }

    public function test_guest_tracking_and_actions_require_a_signed_private_link(): void
    {
        Notification::fake();
        [$category, $room] = $this->reportingOptions();
        $issue = Issue::query()->create([
            'title' => 'AC ruang 101 bermasalah',
            'description' => 'AC tidak mengeluarkan udara dingin.',
            'category_id' => $category->getKey(),
            'location_id' => $room->getKey(),
            'status' => IssueStatus::Reported->value,
        ]);
        $reporter = GuestIssueReporter::query()->create([
            'issue_id' => $issue->getKey(),
            'name' => 'Reporter',
            'email' => 'reporter@example.edu',
            'verification_token_hash' => hash('sha256', Str::random(64)),
            'email_verified_at' => now(),
        ]);
        app(IssueWorkflow::class)->recordGuestInitialReported($issue);
        $issue->forceFill(['status' => IssueStatus::Resolved->value])->saveOrFail();

        $this->get(route('guest.issues.show', ['guestIssueReporter' => $reporter]))
            ->assertForbidden();

        $closeUrl = URL::signedRoute('guest.issues.close', ['guestIssueReporter' => $reporter]);
        $this->post($closeUrl)->assertRedirect();
        $this->assertDatabaseHas('issues', [
            'id' => $issue->getKey(),
            'status' => IssueStatus::Closed->value,
        ]);

        $reopenUrl = URL::signedRoute('guest.issues.reopen', ['guestIssueReporter' => $reporter]);
        $this->post($reopenUrl, ['reason' => 'AC masih belum dingin.'])->assertRedirect();
        $this->assertDatabaseHas('issues', [
            'id' => $issue->getKey(),
            'status' => IssueStatus::Reopened->value,
        ]);
        $this->assertDatabaseCount('status_histories', 3);
    }

    public function test_expired_pending_reports_and_private_photos_are_pruned(): void
    {
        Storage::fake('local');
        [$category, $room] = $this->reportingOptions();
        $photoPath = 'pending-guest-reports/expired.jpg';
        Storage::disk('local')->put($photoPath, 'test image');
        $pending = PendingGuestReport::query()->create([
            'name' => 'Reporter',
            'email' => 'reporter@example.edu',
            'title' => 'Lampu mati',
            'description' => 'Lampu di ruang 101 mati.',
            'category_id' => $category->getKey(),
            'location_id' => $room->getKey(),
            'photo_path' => $photoPath,
            'verification_token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('campusfix:prune-pending-guest-reports')->assertSuccessful();

        $this->assertDatabaseMissing('pending_guest_reports', ['id' => $pending->getKey()]);
        Storage::disk('local')->assertMissing($photoPath);
    }

    /** @return array{Category, Location} */
    private function reportingOptions(): array
    {
        $campus = Location::query()->create([
            'name' => config('campus.location_name'),
            'type' => 'campus',
        ]);
        $faculty = Location::query()->create([
            'name' => 'Fakultas',
            'type' => 'faculty',
            'parent_id' => $campus->getKey(),
        ]);
        $building = Location::query()->create([
            'name' => 'Gedung A',
            'type' => 'building',
            'parent_id' => $faculty->getKey(),
        ]);
        $floor = Location::query()->create([
            'name' => 'Lantai 1',
            'type' => 'floor',
            'parent_id' => $building->getKey(),
        ]);
        $room = Location::query()->create([
            'name' => 'Ruang 101',
            'type' => 'room',
            'parent_id' => $floor->getKey(),
        ]);
        $category = Category::query()->create([
            'name' => 'Jaringan',
            'description' => 'Masalah Wi-Fi dan jaringan.',
            'is_active' => true,
        ]);

        return [$category, $room];
    }
}
