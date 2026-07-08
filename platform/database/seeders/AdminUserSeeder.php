<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@amamaule.cl'],
            [
                'name' => 'Administrador AMA',
                'password' => Hash::make('CambiarClave2026!'),
                'must_change_password' => true,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('super_admin');
    }
}
