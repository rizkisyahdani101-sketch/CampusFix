<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminTechnicianTeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_team_selector_only_shows_for_the_technician_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $memberForm = $this->actingAs($admin)
            ->withSession(['_old_input' => ['role' => User::ROLE_MEMBER]])
            ->get(route('admin.users.create'))
            ->assertOk();

        $this->assertMatchesRegularExpression(
            '/<div id="technician-team-fields"[^>]*\shidden\b/',
            $memberForm->getContent(),
        );

        $technicianForm = $this->withSession(['_old_input' => ['role' => User::ROLE_TECHNICIAN]])
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Belum ada tim.');

        $this->assertDoesNotMatchRegularExpression(
            '/<div id="technician-team-fields"[^>]*\shidden\b/',
            $technicianForm->getContent(),
        );
    }

    public function test_admin_can_assign_team_to_technician_and_create_member_without_a_team(): void
    {
        $this->seed(TeamSeeder::class);
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $team = Team::query()->where('name', 'IT Support')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Teknisi UNIMED',
                'email' => 'teknisi@unimed.ac.id',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_TECHNICIAN,
                'team_ids' => [$team->getKey()],
            ])
            ->assertRedirect(route('admin.users.index'));

        $technician = User::query()->where('email', 'teknisi@unimed.ac.id')->firstOrFail();
        $this->assertDatabaseHas('team_user', [
            'user_id' => $technician->getKey(),
            'team_id' => $team->getKey(),
        ]);

        $this->post(route('admin.users.store'), [
            'name' => 'Member UNIMED',
            'email' => 'member@unimed.ac.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_MEMBER,
        ])->assertRedirect(route('admin.users.index'));

        $member = User::query()->where('email', 'member@unimed.ac.id')->firstOrFail();
        $this->assertDatabaseMissing('team_user', ['user_id' => $member->getKey()]);
    }
}
