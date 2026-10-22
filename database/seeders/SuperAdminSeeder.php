<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Creates the super admin account. The password is never written in the
     * code (the repository is public): set SUPERADMIN_PASSWORD in .env, or a
     * strong random one is generated and shown once in the terminal.
     */
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL', 'superadmin@foundationlink.com');

        if (User::where('email', $email)->exists()) {
            $this->command?->info("Super admin {$email} already exists; left unchanged.");
            return;
        }

        $password = env('SUPERADMIN_PASSWORD') ?: Str::password(16);

        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'superadmin',
            'phone' => env('SUPERADMIN_PHONE'),
            'address' => null,
        ]);

        if (!env('SUPERADMIN_PASSWORD')) {
            $this->command?->warn("Super admin created: {$email}");
            $this->command?->warn("Generated password (shown only once, save it now): {$password}");
        }
    }
}
