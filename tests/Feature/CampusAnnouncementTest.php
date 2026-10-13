<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CampusAnnouncement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CampusAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_hide_and_delete_running_information(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->post(route('admin.announcements.store'), [
            'message' => 'Pemeliharaan Wi-Fi hari Jumat.',
            'is_active' => '1',
        ])->assertRedirect(route('admin.announcements.index'));

        $announcement = CampusAnnouncement::query()->firstOrFail();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Pemeliharaan Wi-Fi hari Jumat.');

        $this->actingAs($admin)->put(route('admin.announcements.update', $announcement), [
            'message' => 'Perpustakaan tutup pukul 17.00.',
        ])->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('campus_announcements', [
            'id' => $announcement->getKey(),
            'message' => 'Perpustakaan tutup pukul 17.00.',
            'is_active' => false,
        ]);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertDontSee('Perpustakaan tutup pukul 17.00.');

        $this->actingAs($admin)->delete(route('admin.announcements.destroy', $announcement))
            ->assertRedirect(route('admin.announcements.index'));
        $this->assertDatabaseMissing('campus_announcements', ['id' => $announcement->getKey()]);
    }

    public function test_members_cannot_manage_running_information(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('admin.announcements.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_manage_running_information(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($superAdmin)
            ->get(route('admin.announcements.index'))
            ->assertOk()
            ->assertSee('Informasi kampus');
    }
}
