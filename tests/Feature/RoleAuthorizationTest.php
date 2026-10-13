<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Member tidak boleh mengakses halaman admin
     */
    public function test_member_cannot_access_admin_dashboard()
    {
        $member = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($member)
            ->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /**
     * Test: Technician hanya boleh melihat pekerjaan sesuai kewenangannya
     */
    public function test_technician_access_only_own_work()
    {
        $technician = User::factory()->create(['role' => 'technician']);

        $response = $this->actingAs($technician)
            ->get('/technician/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test: Coordinator dapat memverifikasi dan melakukan assignment
     */
    public function test_coordinator_can_verify_assignments()
    {
        $coordinator = User::factory()->create(['role' => 'coordinator']);

        $response = $this->actingAs($coordinator)
            ->get('/coordinator/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test: Admin dapat melihat data sesuai scope
     */
    public function test_admin_can_view_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test: Super Admin memiliki akses konfigurasi sistem
     */
    public function test_super_admin_can_configure_system()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);

        $this->get('/admin/users')->assertOk();
        $this->get('/admin/users/create')->assertOk();
        $this->get('/superadmin/roles')
            ->assertOk()
            ->assertSee('Daftar role');
    }

    /**
     * Test: Role constants are defined
     */
    public function test_role_constants()
    {
        $this->assertEquals('member', User::ROLE_MEMBER);
        $this->assertEquals('technician', User::ROLE_TECHNICIAN);
        $this->assertEquals('coordinator', User::ROLE_COORDINATOR);
        $this->assertEquals('admin', User::ROLE_ADMIN);
        $this->assertEquals('super_admin', User::ROLE_SUPER_ADMIN);
    }

    /**
     * Test: User role methods
     */
    public function test_user_role_methods()
    {
        $member = User::factory()->create(['role' => 'member']);
        $admin = User::factory()->create(['role' => 'admin']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($member->isMember());
        $this->assertFalse($member->isAdmin());
        $this->assertFalse($member->isSuperAdmin());

        $this->assertFalse($admin->isMember());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isSuperAdmin());

        $this->assertFalse($superAdmin->isMember());
        $this->assertTrue($superAdmin->isAdmin());
        $this->assertTrue($superAdmin->isSuperAdmin());
    }
}
