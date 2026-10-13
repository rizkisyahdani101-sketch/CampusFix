<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Technician\IssueQueue;
use App\Models\Assignment;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Location;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class TechnicianIssueQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_comments_render_relative_timestamps(): void
    {
        $technician = User::factory()->create(['role' => User::ROLE_TECHNICIAN]);
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $team = Team::query()->create(['name' => 'Facility']);
        $category = Category::query()->create(['name' => 'AC']);
        $location = Location::query()->create(['name' => 'Gedung A', 'type' => 'building']);
        $issue = Issue::query()->create([
            'title' => 'AC tidak dingin',
            'description' => 'AC ruang kelas tidak dingin.',
            'category_id' => $category->getKey(),
            'location_id' => $location->getKey(),
            'status' => 'in_progress',
        ]);

        Assignment::query()->create([
            'issue_id' => $issue->getKey(),
            'team_id' => $team->getKey(),
            'technician_id' => $technician->getKey(),
            'assigned_by' => $coordinator->getKey(),
            'assigned_at' => now(),
            'accepted_at' => now(),
        ]);
        Comment::query()->create([
            'issue_id' => $issue->getKey(),
            'user_id' => $technician->getKey(),
            'body' => 'Filter AC sedang dibersihkan.',
        ]);

        Livewire::actingAs($technician)
            ->test(IssueQueue::class)
            ->assertSee('Filter AC sedang dibersihkan.')
            ->assertSee('ago');
    }
}
