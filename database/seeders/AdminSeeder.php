<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.fr'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Yk9$mPx2@vQ7!nLd'),
                'balance' => 999999,
            ]
        );

        $admin->assignRole('admin');
    }
}
