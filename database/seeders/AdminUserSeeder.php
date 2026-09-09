<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed Administrator account dynamically from environment or defaults.
     */
    public function run(): void
    {
        $adminName = env('ADMIN_NAME', 'CarelioEMR Super Admin');
        $adminEmail = env('ADMIN_EMAIL', 'admin@carelioemr.com');
        $adminPassword = env('ADMIN_PASSWORD', 'admin123');

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]
        );
    }
}
