<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User can login with valid credentials
     */
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'member',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/member/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test: User is redirected to correct dashboard based on role
     */
    public function test_user_redirected_to_correct_dashboard()
    {
        $dashboards = [
            'member' => '/member/dashboard',
            'technician' => '/technician/dashboard',
            'coordinator' => '/coordinator/dashboard',
            'admin' => '/admin/dashboard',
            'super_admin' => '/superadmin/dashboard',
        ];

        foreach ($dashboards as $role => $path) {
            $this->post('/logout');
            $user = User::factory()->create(['role' => $role]);

            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect($path);
        }
    }

    /**
     * Test: User cannot login with invalid credentials
     */
    public function test_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test: User can register
     */
    public function test_user_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'member',
        ]);

        $response->assertRedirect('/member/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    /**
     * Test: User can logout
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();
    }

    /**
     * Test: Login page is accessible
     */
    public function test_login_page_is_accessible()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /**
     * Test: Register page is accessible
     */
    public function test_register_page_is_accessible()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    /**
     * Test: Unauthenticated user is redirected to login
     */
    public function test_unauthenticated_redirects_to_login()
    {
        $response = $this->get('/member/dashboard');
        $response->assertRedirect('/login');
    }
}
