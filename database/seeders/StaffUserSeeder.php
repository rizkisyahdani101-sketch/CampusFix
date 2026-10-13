<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        $facility = Team::query()->where('name', 'like', '%Facility%')->first();
        $itSupport = Team::query()->where('name', 'like', '%IT Support%')->first();

        if ($facility === null || $itSupport === null) {
            throw new \RuntimeException('Run TeamSeeder first: Facility / IT Support teams are missing.');
        }

        User::query()->firstOrCreate(
            ['email' => 'koordinator@campusfix.test'],
            [
                'name' => 'Koordinator UNIMED',
                'password' => Hash::make('koordinator123milik12'),
                'role' => User::ROLE_COORDINATOR,
                'is_active' => true,
            ],
        );

        $facilityTechnician = User::query()->firstOrCreate(
            ['email' => 'teknisi.facility@campusfix.test'],
            [
                'name' => 'Teknisi Facility',
                'password' => Hash::make('teknisi123milik12'),
                'role' => User::ROLE_TECHNICIAN,
                'is_active' => true,
            ],
        );
        $facilityTechnician->teams()->sync([$facility->getKey()]);

        $itTechnician = User::query()->firstOrCreate(
            ['email' => 'teknisi.it@campusfix.test'],
            [
                'name' => 'Teknisi IT Support',
                'password' => Hash::make('teknisi123milik12'),
                'role' => User::ROLE_TECHNICIAN,
                'is_active' => true,
            ],
        );
        $itTechnician->teams()->sync([$itSupport->getKey()]);
    }
}
