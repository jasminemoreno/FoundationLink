<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Forgot password → reset: the new password must have at least 12 characters,
 * a number and a special character. Same page and same rule for donors and
 * foundation admins.
 */
class ResetPasswordRulesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'first_name' => 'Test',
            'last_name' => "User{$n}",
            'email' => "reset{$n}@example.com",
            'password' => 'Secret-pass-123!',
            'role' => $role,
            'phone' => '+63917100' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'status' => 'active',
        ]);
    }

    private function reset(User $user, string $password)
    {
        return $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'reset_token' => 'reset-token',
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    public function test_a_weak_new_password_is_refused_for_donors_and_foundation_admins(): void
    {
        foreach (['donor', 'foundation_admin'] as $role) {
            $user = $this->makeUser($role);
            DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => Hash::make('reset-token'),
                'created_at' => now(),
            ]);

            // 11 characters, has a number and a special character
            $this->reset($user, 'Short-pass1')
                ->assertStatus(422)
                ->assertJsonPath('errors.password.0', 'Password must be at least 12 characters.');

            // long enough, but no number
            $this->reset($user, 'No-numbers-here!')
                ->assertStatus(422)
                ->assertJsonPath('errors.password.0', 'Password must include at least 1 number and 1 special character.');

            // long enough, but no special character
            $this->reset($user, 'NoSpecialChar123')
                ->assertStatus(422)
                ->assertJsonPath('errors.password.0', 'Password must include at least 1 number and 1 special character.');

            $this->assertTrue(Hash::check('Secret-pass-123!', $user->fresh()->password), "{$role}: password changed by a weak reset");

            // 12+ characters with a number and a special character is accepted
            $this->reset($user, 'Brand-new-pass-7')->assertOk();
            $this->assertTrue(Hash::check('Brand-new-pass-7', $user->fresh()->password), "{$role}: strong reset did not save");
        }
    }
}
