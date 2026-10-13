<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Coordinator\IssueAssignmentQueue;
use App\Models\Category;
use App\Models\Issue;
use App\Models\Location;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class IssueAssignmentQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_a_team_loads_only_its_technicians_and_clears_old_selection(): void
    {
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $facility = Team::query()->create(['name' => 'Facility']);
        $itSupport = Team::query()->create(['name' => 'IT Support']);
        $technician = User::factory()->create([
            'name' => 'Teknisi Facility',
            'role' => User::ROLE_TECHNICIAN,
        ]);
        $technician->teams()->attach($facility);

        $category = Category::query()->create(['name' => 'Lampu']);
        $location = Location::query()->create(['name' => 'Gedung A', 'type' => 'building']);
        $issue = Issue::query()->create([
            'title' => 'Lampu rusak',
            'description' => 'Lampu ruang kelas mati.',
            'category_id' => $category->getKey(),
            'location_id' => $location->getKey(),
            'status' => 'verified',
        ]);

        Livewire::actingAs($coordinator)
            ->test(IssueAssignmentQueue::class)
            ->assertDontSee('Teknisi Facility')
            ->set("teamIds.{$issue->getKey()}", $facility->getKey())
            ->assertSee('Teknisi Facility')
            ->set("technicianIds.{$issue->getKey()}", $technician->getKey())
            ->set("teamIds.{$issue->getKey()}", $itSupport->getKey())
            ->assertDontSee('Teknisi Facility')
            ->assertSee('Belum ada teknisi di tim ini. Minta admin menambahkan anggota ke tim tersebut.')
            ->assertSet("technicianIds.{$issue->getKey()}", null);
    }
}
