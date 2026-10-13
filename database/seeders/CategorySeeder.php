<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Wi-Fi',
            'Lampu',
            'AC',
            'Proyektor',
            'Kursi dan Meja',
            'Toilet',
            'Listrik',
            'Fasilitas Lainnya',
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate([
                'name' => $category,
            ]);
        }
    }
}