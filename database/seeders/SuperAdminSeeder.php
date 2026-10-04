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
            'email' => 'superadmin@foundationlink.com',
            'password' => Hash::make('@superadmin//domaincapstone2'),
            'role' => 'superadmin',
            'phone' => '09773936631',
            'address' => null,
        ]);
    }
}