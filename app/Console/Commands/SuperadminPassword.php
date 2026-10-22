<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Sets a new password for the super admin (there is no change-password page
 * for that role). Asks in the terminal with hidden input, so the password is
 * never written in a file, the code or the command line, and logs the account
 * out everywhere afterwards.
 *
 *   php artisan superadmin:password
 *   php artisan superadmin:password --email=other@foundationlink.com
 */
class SuperadminPassword extends Command
{
    protected $signature = 'superadmin:password {--email= : Which super admin (default: the only / first one)}';

    protected $description = 'Set a new password for the super admin (typed hidden in the terminal)';

    public function handle(): int
    {
        $query = User::where('role', 'superadmin');
        if ($email = $this->option('email')) {
            $query->where('email', $email);
        }
        $admin = $query->orderBy('id')->first();

        if (!$admin) {
            $this->error('No super admin account found' . ($email ? " with email {$email}" : '') . '.');
            return self::FAILURE;
        }

        $this->info("Changing the password for super admin: {$admin->email}");
        $this->line('Rules: at least 12 characters, with a number and a special character. What you type is hidden.');

        $password = (string) $this->secret('New password');
        $confirm = (string) $this->secret('Type it again');

        // Same rules as sign-up and password reset
        if (mb_strlen($password) < 12 || !preg_match('/\d/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $this->error('Not changed: the password must be at least 12 characters and include a number and a special character.');
            return self::FAILURE;
        }
        if (!hash_equals($password, $confirm)) {
            $this->error('Not changed: the two passwords do not match.');
            return self::FAILURE;
        }

        $admin->forceFill(['password' => Hash::make($password)])->save();
        $loggedOut = $admin->tokens()->count();
        $admin->tokens()->delete(); // anyone logged in with the old password is signed out

        $this->info("Done. The password was changed and {$loggedOut} login session(s) were signed out.");
        return self::SUCCESS;
    }
}
