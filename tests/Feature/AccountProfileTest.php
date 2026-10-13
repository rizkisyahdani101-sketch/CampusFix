<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AccountProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_account_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'student_id' => '2012345678',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)->get('/account');

        $response->assertOk();
        $response->assertSee('Budi Santoso');
        $response->assertSee('budi@example.test');
        $response->assertSee('2012345678');
    }

    public function test_guest_cannot_access_profile(): void
    {
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password-123'),
        ]);

        $response = $this->actingAs($user)->put('/account/password', [
            'current_password' => 'old-password-123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertRedirect('/account');
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-456', $user->fresh()->password));
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password-123'),
        ]);

        $response = $this->from('/account')->actingAs($user)->put('/account/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertRedirect('/account');
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_new_password_must_differ_from_current(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('same-password-1'),
        ]);

        $response = $this->from('/account')->actingAs($user)->put('/account/password', [
            'current_password' => 'same-password-1',
            'password' => 'same-password-1',
            'password_confirmation' => 'same-password-1',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_must_be_confirmed(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password-123'),
        ]);

        $response = $this->from('/account')->actingAs($user)->put('/account/password', [
            'current_password' => 'old-password-123',
            'password' => 'new-password-456',
            'password_confirmation' => 'different-789',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_session_survives_after_password_change(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password-123'),
        ]);

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'old-password-123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        // The current session must remain authenticated after the change.
        $this->get('/account')->assertOk();

        // New password must be accepted at login.
        $this->post('/logout');
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'new-password-456',
        ]);

        $this->assertAuthenticated();
    }

    public function test_every_role_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');

        foreach (User::getRoles() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->put('/account/avatar', [
                'avatar' => UploadedFile::fake()->image('foto-'.$role.'.jpg', 240, 240),
            ]);

            $response->assertRedirect('/account');
            $response->assertSessionHas('success');

            $path = $user->fresh()->avatar;

            $this->assertNotNull($path, 'Role '.$role.' tidak dapat menyimpan foto profil.');
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_replacing_a_profile_photo_removes_the_previous_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => User::ROLE_TECHNICIAN]);

        $this->actingAs($user)->put('/account/avatar', [
            'avatar' => UploadedFile::fake()->image('lama.jpg', 120, 120),
        ]);

        $previous = $user->fresh()->avatar;

        $this->actingAs($user)->put('/account/avatar', [
            'avatar' => UploadedFile::fake()->image('baru.png', 120, 120),
        ]);

        $current = $user->fresh()->avatar;

        $this->assertNotSame($previous, $current);
        Storage::disk('public')->assertExists($current);
        Storage::disk('public')->assertMissing($previous);
    }

    public function test_profile_photo_must_be_an_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->from('/account')->actingAs($user)->put('/account/avatar', [
            'avatar' => UploadedFile::fake()->create('dokumen.pdf', 120, 'application/pdf'),
        ]);

        $response->assertRedirect('/account');
        $response->assertSessionHasErrors('avatar');
        $this->assertNull($user->fresh()->avatar);
        Storage::disk('public')->assertDirectoryEmpty('avatars');
    }

    public function test_profile_photo_upload_requires_a_file(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/account')->actingAs($user)->put('/account/avatar', []);

        $response->assertSessionHasErrors('avatar');
    }

    public function test_user_can_remove_their_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->put('/account/avatar', [
            'avatar' => UploadedFile::fake()->image('foto.jpg', 120, 120),
        ]);

        $path = $user->fresh()->avatar;

        $response = $this->actingAs($user)->delete('/account/avatar');

        $response->assertRedirect('/account');
        $response->assertSessionHas('success');
        $this->assertNull($user->fresh()->avatar);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_portal_shows_the_profile_photo_and_initials_fallback(): void
    {
        $withPhoto = User::factory()->create([
            'name' => 'Rizki Foto',
            'role' => User::ROLE_COORDINATOR,
            'avatar' => 'avatars/rizki.jpg',
        ]);

        $this->actingAs($withPhoto)->get('/account')
            ->assertOk()
            ->assertSee('storage/avatars/rizki.jpg')
            ->assertSee('Foto profil Rizki Foto');

        $withoutPhoto = User::factory()->create([
            'name' => 'Budi Santoso',
            'role' => User::ROLE_MEMBER,
        ]);

        $this->actingAs($withoutPhoto)->get('/account')
            ->assertOk()
            ->assertSee('BS');
    }
}
