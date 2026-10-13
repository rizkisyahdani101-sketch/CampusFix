<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

final class TeamSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Facility', 'IT Support'] as $name) {
            Team::query()->firstOrCreate(['name' => $name]);
        }
    }
}
