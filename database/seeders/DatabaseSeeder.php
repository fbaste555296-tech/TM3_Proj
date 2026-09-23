<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@triplem3.test'],
            ['name' => 'Triple-M3 Administrator', 'phone' => '0917-000-0000', 'role' => 'admin', 'password' => Hash::make('Admin123!')]
        );
    }
}
