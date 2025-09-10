<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Category;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Agent',
            'email' => 'agent@example.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
        ]);

        User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        Category::insert([
            ['name' => 'General', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Billing', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Technical', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
