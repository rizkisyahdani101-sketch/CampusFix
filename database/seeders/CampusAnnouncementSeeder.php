<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CampusAnnouncement;
use Illuminate\Database\Seeder;

final class CampusAnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        if (CampusAnnouncement::query()->exists()) {
            return;
        }

        CampusAnnouncement::query()->create([
            'message' => 'Selamat datang di CampusFix UNIMED. Laporkan kendala fasilitas kampus agar dapat segera ditindaklanjuti.',
            'is_active' => true,
        ]);
    }
}
