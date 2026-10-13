<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            LocationSeeder::class,
            TeamSeeder::class,
            SuperAdminSeeder::class,
            CampusAnnouncementSeeder::class,
        ]);
    }
}
