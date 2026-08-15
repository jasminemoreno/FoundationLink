<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'yoshiime77@gmail.com',
            'password' => Hash::make('admin123'),
            'role' => 'superadmin',
            'phone' => null,
            'address' => null,
        ]);
    }
}