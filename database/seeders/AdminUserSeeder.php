<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@kayaa.lk')],
            [
                'name' => env('ADMIN_NAME', 'Kayaa Admin'),
                'password' => env('ADMIN_PASSWORD', 'change-me-now'),
                'is_admin' => true,
            ],
        );
    }
}
