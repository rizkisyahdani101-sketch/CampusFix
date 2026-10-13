<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $campus = Location::query()
            ->where('type', 'campus')
            ->whereNull('parent_id')
            ->first() ?? new Location(['type' => 'campus']);

        $campus->forceFill([
            'name' => config('campus.location_name'),
            'type' => 'campus',
            'parent_id' => null,
        ])->save();

        // Faculty names follow https://www.unimed.ac.id/; the official site
        // counts the Sekolah Pascasarjana separately in its 9-unit total.
        $faculties = [
            'FIP' => 'Fakultas Ilmu Pendidikan (FIP)',
            'FBS' => 'Fakultas Bahasa dan Seni (FBS)',
            'FIS' => 'Fakultas Ilmu Sosial (FIS)',
            'FMIPA' => 'Fakultas Matematika dan Ilmu Pengetahuan Alam (FMIPA)',
            'FT' => 'Fakultas Teknik (FT)',
            'FIK' => 'Fakultas Ilmu Keolahragaan (FIK)',
            'FE' => 'Fakultas Ekonomi (FE)',
            'FK' => 'Fakultas Kedokteran (FK)',
            'PPS' => 'Sekolah Pascasarjana (PPS)',
        ];

        $facultyLocations = [];

        foreach ($faculties as $code => $name) {
            $facultyLocations[$code] = Location::query()->firstOrCreate([
                'name' => $name,
                'type' => 'faculty',
                'parent_id' => $campus->getKey(),
            ]);
        }

        // Keep the old demo row identifiable if historical reports reference it.
        Location::query()
            ->where('type', 'building')
            ->where('name', 'Gedung Lab Komputer')
            ->where('parent_id', $facultyLocations['FMIPA']->getKey())
            ->update(['name' => 'Lokasi contoh lama (perlu verifikasi)']);

        // Building references: pgsd.unimed.ac.id/lab-terpadu/,
        // uptbahasa.unimed.ac.id/about, and the official UNIMED news site.
        // Exact room and floor directories are not publicly listed.
        $buildingsByFaculty = [
            'FIP' => ['Gedung 72 FIP'],
            'FBS' => ['Gedung FBS'],
            'FIS' => ['Gedung FIS'],
            'FMIPA' => ['Gedung FMIPA'],
            'FT' => ['Gedung FT'],
            'FIK' => ['Gedung FIK'],
            'PPS' => ['Gedung 45 Pascasarjana'],
        ];

        foreach ($buildingsByFaculty as $facultyCode => $buildingNames) {
            foreach ($buildingNames as $name) {
                Location::query()->firstOrCreate([
                    'name' => $name,
                    'type' => 'building',
                    'parent_id' => $facultyLocations[$facultyCode]->getKey(),
                ]);
            }
        }

        // Campus-wide facilities named on the UNIMED campus map and unit pages:
        // https://digilib.unimed.ac.id/id/eprint/47724/1/document.pdf
        foreach ([
            'Gedung Biro Rektor UNIMED',
            'Auditorium UNIMED',
            'Digital Library UNIMED',
            'PUSKOM UNIMED',
            'Masjid Baiturrahman UNIMED',
            'Stadion UNIMED',
            'Gelanggang Mahasiswa Haji Anif',
            'SCC Swimming Pool UNIMED',
        ] as $name) {
            Location::query()->firstOrCreate([
                'name' => $name,
                'type' => 'building',
                'parent_id' => $campus->getKey(),
            ]);
        }

    }
}
