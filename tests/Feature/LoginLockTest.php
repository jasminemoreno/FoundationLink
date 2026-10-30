<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Login attempts (website and app use the same /api/login):
 * - each wrong try says how many tries are left;
 * - from the 3rd wrong try, a "forgot your password?" hint;
 * - 5 wrong tries lock login for 5 minutes (even with the right password);
 * - failing again after a lock adds "contact support";
 * - a successful login starts over.
 * Counted per email + network address, so one person can't lock out others.
 */
class LoginLockTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Secret-pass-123!';

    private function makeUser(string $email = 'dana@example.com'): User
    {
        return User::create([
            'first_name' => 'Dana', 'last_name' => 'Tester', 'email' => $email,
            'password' => self::PASSWORD, 'role' => 'donor',
            'phone' => '+639170000' . random_int(100, 999), 'status' => 'active',
        ]);
    }

    private function login(string $email, string $password, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/login', ['email' => $email, 'password' => $password]);
    }

    public function test_each_wrong_try_says_how_many_are_left_and_the_forgot_hint_starts_at_the_third(): void
    {
        $user = $this->makeUser();

        foreach ([[4, false], [3, false], [2, true], [1, true]] as $i => [$left, $forgot]) {
            $this->login($user->email, 'Wrong-pass-999!')
                ->assertStatus(422)
                ->assertJsonPath('code', 'invalid_credentials')
                ->assertJsonPath('attempts_left', $left)
                ->assertJsonPath('show_forgot', $forgot)
                ->assertJsonPath('show_support', false);
        }
    }

    public function test_the_fifth_wrong_try_locks_login_for_five_minutes_even_with_the_right_password(): void
    {
        $user = $this->makeUser();
        for ($i = 0; $i < 4; $i++) {
            $this->login($user->email, 'Wrong-pass-999!')->assertStatus(422);
        }

        $locked = $this->login($user->email, 'Wrong-pass-999!')
            ->assertStatus(429)
            ->assertJsonPath('code', 'login_locked')
            ->assertJsonPath('show_forgot', true)
            ->assertJsonPath('show_support', false);
        $this->assertEqualsWithDelta(300, $locked->json('retry_after'), 2);
        $this->assertStringContainsString('5 minutes', $locked->json('message'));

        // Still locked, even with the correct password
        $this->travel(4)->minutes();
        $this->login($user->email, self::PASSWORD)->assertStatus(429)->assertJsonPath('code', 'login_locked');

        // After 5 minutes the right password works again
        $this->travel(2)->minutes();
        $this->login($user->email, self::PASSWORD)->assertOk()->assertJsonStructure(['token']);
    }

    public function test_failing_again_after_a_lock_adds_contact_support_and_locks_again(): void
    {
        $user = $this->makeUser();
        for ($i = 0; $i < 5; $i++) {
            $this->login($user->email, 'Wrong-pass-999!');
        }
        $this->travel(6)->minutes();

        $this->login($user->email, 'Wrong-pass-999!')
            ->assertStatus(422)
            ->assertJsonPath('show_forgot', true)
            ->assertJsonPath('show_support', true)
            ->assertJsonStructure(['support_email']);

        for ($i = 0; $i < 3; $i++) {
            $this->login($user->email, 'Wrong-pass-999!')->assertStatus(422);
        }
        $second = $this->login($user->email, 'Wrong-pass-999!')
            ->assertStatus(429)
            ->assertJsonPath('code', 'login_locked')
            ->assertJsonPath('show_support', true);
        $this->assertStringContainsString('support', strtolower($second->json('message')));
    }

    public function test_a_successful_login_starts_the_count_over(): void
    {
        $user = $this->makeUser();
        $this->login($user->email, 'Wrong-pass-999!');
        $this->login($user->email, 'Wrong-pass-999!');
        $this->login($user->email, self::PASSWORD)->assertOk();

        $this->login($user->email, 'Wrong-pass-999!')->assertJsonPath('attempts_left', 4);
    }

    public function test_one_person_cannot_lock_out_others(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser('other@example.com');
        for ($i = 0; $i < 5; $i++) {
            $this->login($user->email, 'Wrong-pass-999!', '10.0.0.1');
        }

        // the same account from another network, and another account on the same network
        $this->login($user->email, self::PASSWORD, '10.0.0.2')->assertOk();
        $this->login($other->email, self::PASSWORD, '10.0.0.1')->assertOk();
    }

    public function test_the_message_never_reveals_whether_the_email_exists(): void
    {
        $this->makeUser();

        $known = $this->login('dana@example.com', 'Wrong-pass-999!')->json();
        $unknown = $this->login('nobody@example.com', 'Wrong-pass-999!')->json();

        $this->assertSame($known['message'], $unknown['message']);
        $this->assertSame($known['attempts_left'], $unknown['attempts_left']);
    }
}
