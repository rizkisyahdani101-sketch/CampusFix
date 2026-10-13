<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HelpAndCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_help_page_is_available_to_guests_and_links_to_guest_reporting(): void
    {
        $this->get(route('help'))
            ->assertOk()
            ->assertSee('Pusat bantuan')
            ->assertSee('Laporkan tanpa akun')
            ->assertSee(route('guest.issues.create'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('help'));
    }

    public function test_authenticated_member_can_open_help_and_find_member_actions(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('help'))
            ->assertOk()
            ->assertSee('Pusat bantuan')
            ->assertSee(route('issues.create'))
            ->assertSee(route('reporter.issues.confirmation'));
    }

    public function test_facilities_other_category_is_last_on_report_form_and_member_home(): void
    {
        $this->seed(CategorySeeder::class);

        $expectedOrder = [
            'AC',
            'Kursi dan Meja',
            'Lampu',
            'Listrik',
            'Proyektor',
            'Toilet',
            'Wi-Fi',
            'Fasilitas Lainnya',
        ];

        $this->get(route('guest.issues.create'))
            ->assertOk()
            ->assertSeeInOrder($expectedOrder);

        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('issues.create'))
            ->assertOk()
            ->assertSeeInOrder($expectedOrder);

        $this->get(route('member.dashboard'))
            ->assertOk()
            ->assertSeeInOrder($expectedOrder);
    }
}
