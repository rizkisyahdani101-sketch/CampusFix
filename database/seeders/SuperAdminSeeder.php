<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

final class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('CAMPUSFIX_SUPER_ADMIN_EMAIL', ''));
        $password = (string) env('CAMPUSFIX_SUPER_ADMIN_PASSWORD', '');

        if ($email === '' && $password === '') {
            return;
        }

        if ($email === '' || strlen($password) < 12) {
            throw new InvalidArgumentException(
                'Set CAMPUSFIX_SUPER_ADMIN_EMAIL and a password of at least 12 characters before seeding the initial super admin.',
            );
        }

        $user = User::query()->firstOrCreate(
            ['email' => mb_strtolower($email)],
            [
                'name' => env('CAMPUSFIX_SUPER_ADMIN_NAME') ?: 'CampusFix UNIMED Super Admin',
                'password' => Hash::make($password),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ],
        );

        if (! $user->wasRecentlyCreated && $user->role !== User::ROLE_SUPER_ADMIN) {
            throw new InvalidArgumentException(
                'The configured initial super admin email already belongs to a non-super-admin account.',
            );
        }
    }
}
