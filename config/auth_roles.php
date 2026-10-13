<?php

/**
 * Application Configuration
 */
return [
    'app' => [
        'name' => 'CampusFix UNIMED',
        'version' => '1.0.0',
        'environment' => env('APP_ENV', 'production'),
    ],

    'roles' => [
        'member' => [
            'label' => 'Member',
            'description' => 'Akses dasar',
            'permissions' => [
                'view_profile',
                'update_own_profile',
            ],
        ],
        'technician' => [
            'label' => 'Technician',
            'description' => 'Lihat pekerjaan sesuai kewenangannya',
            'permissions' => [
                'view_assigned_work',
                'complete_work',
            ],
        ],
        'coordinator' => [
            'label' => 'Coordinator',
            'description' => 'Verifikasi dan assignment',
            'permissions' => [
                'verify_assignments',
                'assign_work',
            ],
        ],
        'admin' => [
            'label' => 'Admin',
            'description' => 'Kelola data sesuai scope',
            'permissions' => [
                'view_all_data',
                'manage_users',
            ],
        ],
        'super_admin' => [
            'label' => 'Super Admin',
            'description' => 'Akses konfigurasi sistem',
            'permissions' => [
                'configure_system',
                'manage_all',
            ],
        ],
    ],
];
