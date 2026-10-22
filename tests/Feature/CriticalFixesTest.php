<?php

namespace Tests\Feature;

use App\Models\Foundation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CriticalFixesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, array $overrides = []): User
    {
        static $n = 0;
        $n++;

        return User::create($overrides + [
            'first_name' => 'Test',
            'last_name' => "User{$n}",
            'email' => "user{$n}@example.com",
            'password' => 'Secret-pass-123!',
            'role' => $role,
            'phone' => '+63917000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'status' => 'active',
        ]);
    }

    private function makeFoundation(User $owner, string $status): Foundation
    {
        return Foundation::create([
            'user_id' => $owner->id,
            'name' => 'Foundation of ' . $owner->last_name,
            'status' => $status,
        ]);
    }

    private function verificationPayload(): array
    {
        return [
            'admin_id_type' => 'passport',
            'doc_type' => 'sec_registration',
            'admin_files' => [UploadedFile::fake()->image('id.jpg')],
            'doc_files' => [UploadedFile::fake()->image('doc.jpg')],
        ];
    }

    /* ── 1. Creating a foundation ── */

    public function test_creating_a_foundation_requires_login(): void
    {
        $this->postJson('/api/foundations', ['name' => 'Fake'])->assertStatus(401);
    }

    public function test_foundation_sign_up_flow_uses_the_registration_token_not_a_body_user_id(): void
    {
        Storage::fake('public');
        $victim = $this->makeUser('foundation_admin');

        $register = $this->postJson('/api/foundation-admin/register', [
            'first_name' => 'New',
            'last_name' => 'Admin',
            'email' => 'new-admin@example.com',
            'phone' => '09171234567',
            'password' => 'Secret-pass-123!',
            'password_confirmation' => 'Secret-pass-123!',
        ])->assertOk();

        $token = $register->json('registration_token');
        $newAdminId = $register->json('user.id');
        $this->assertNotEmpty($token);

        // Even if the body names someone else, the foundation belongs to the caller.
        $this->withToken($token)
            ->postJson('/api/foundations', ['name' => 'My Foundation', 'user_id' => $victim->id])
            ->assertStatus(201);

        $this->assertSame(0, Foundation::where('user_id', $victim->id)->count());
        $this->assertSame(1, Foundation::where('user_id', $newAdminId)->count());

        // Pressing Back and submitting again updates the draft, no duplicate.
        $this->withToken($token)
            ->postJson('/api/foundations', ['name' => 'Renamed Foundation'])
            ->assertOk();
        $this->assertSame(1, Foundation::where('user_id', $newAdminId)->count());
        $this->assertSame('Renamed Foundation', Foundation::where('user_id', $newAdminId)->value('name'));

        $this->withToken($token)
            ->post('/api/foundation-verification', $this->verificationPayload(), ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame('under_review', Foundation::where('user_id', $newAdminId)->value('status'));
        // Registration token is retired once sign-up is finished.
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $newAdminId)->count());
    }

    /* ── 2. Submitting verification documents ── */

    public function test_verification_requires_login(): void
    {
        $this->postJson('/api/foundation-verification', [])->assertStatus(401);
    }

    public function test_verification_cannot_push_a_verified_or_suspended_foundation_back_into_review(): void
    {
        Storage::fake('public');

        foreach (['verified', 'suspended'] as $status) {
            $admin = $this->makeUser('foundation_admin');
            $foundation = $this->makeFoundation($admin, $status);

            Sanctum::actingAs($admin);
            $this->post('/api/foundation-verification', $this->verificationPayload(), ['Accept' => 'application/json'])
                ->assertStatus(422);

            $this->assertSame($status, $foundation->fresh()->status);
        }
    }

    public function test_verification_ignores_a_foundation_id_in_the_body(): void
    {
        Storage::fake('public');
        $other = $this->makeFoundation($this->makeUser('foundation_admin'), 'verified');

        $admin = $this->makeUser('foundation_admin');
        $own = $this->makeFoundation($admin, 'pending_verification');

        Sanctum::actingAs($admin);
        $this->post('/api/foundation-verification', $this->verificationPayload() + ['foundation_id' => $other->id], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame('verified', $other->fresh()->status);
        $this->assertSame('under_review', $own->fresh()->status);
    }

    /* ── 3. Password reset codes ── */

    private function storeOtp(string $email, string $otp, \DateTimeInterface $createdAt): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($otp),
            'created_at' => $createdAt,
        ]);
    }

    public function test_an_otp_older_than_ten_minutes_is_rejected(): void
    {
        $user = $this->makeUser('donor');
        $this->storeOtp($user->email, '123456', now()->subMinutes(11));

        $this->postJson('/api/verify-otp', ['email' => $user->email, 'otp' => '123456'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This code has expired. Please request a new one.');
    }

    public function test_a_fresh_otp_is_accepted(): void
    {
        $user = $this->makeUser('donor');
        $this->storeOtp($user->email, '123456', now()->subMinutes(2));

        $this->postJson('/api/verify-otp', ['email' => $user->email, 'otp' => '123456'])
            ->assertOk()
            ->assertJsonStructure(['reset_token']);
    }

    public function test_an_otp_is_thrown_away_after_five_wrong_guesses(): void
    {
        $user = $this->makeUser('donor');
        $this->storeOtp($user->email, '123456', now());

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/verify-otp', ['email' => $user->email, 'otp' => '000000'])
                ->assertJsonPath('message', 'Invalid verification code.');
        }

        $this->postJson('/api/verify-otp', ['email' => $user->email, 'otp' => '000000'])
            ->assertJsonPath('message', 'Too many incorrect attempts. Please request a new code.');

        $this->assertFalse(DB::table('password_reset_tokens')->where('email', $user->email)->exists());
    }

    public function test_an_expired_reset_token_cannot_change_the_password(): void
    {
        $user = $this->makeUser('donor');
        $this->storeOtp($user->email, 'reset-token', now()->subMinutes(16));

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'reset_token' => 'reset-token',
            'password' => 'Another-pass-123!',
            'password_confirmation' => 'Another-pass-123!',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('Secret-pass-123!', $user->fresh()->password));
    }

    public function test_login_rate_limit_is_per_account_so_a_shared_network_is_not_locked_out(): void
    {
        $victim = $this->makeUser('donor');
        $colleague = $this->makeUser('donor');

        // Five wrong guesses on one account are allowed, the sixth is blocked...
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => $victim->email, 'password' => 'wrong-guess'])->assertStatus(422);
        }
        $this->postJson('/api/login', ['email' => $victim->email, 'password' => 'wrong-guess'])->assertStatus(429);

        // ...but someone else on the same IP can still log in.
        $this->postJson('/api/login', ['email' => $colleague->email, 'password' => 'Secret-pass-123!'])->assertOk();
    }

    /* ── 4. Suspending a foundation ── */

    public function test_superadmin_can_suspend_and_unsuspend_a_foundation(): void
    {
        $foundation = $this->makeFoundation($this->makeUser('foundation_admin'), 'verified');
        Sanctum::actingAs($this->makeUser('superadmin'));

        $this->patchJson("/api/admin/foundations/{$foundation->id}/suspend")->assertOk();
        $this->assertSame('suspended', $foundation->fresh()->status);

        $this->patchJson("/api/admin/foundations/{$foundation->id}/unsuspend")->assertOk();
        $this->assertSame('verified', $foundation->fresh()->status);
    }

    /* ── 5 & 6. Routes the frontend calls ── */

    public function test_pending_approvals_count_is_served_where_the_sidebar_calls_it(): void
    {
        $this->makeFoundation($this->makeUser('foundation_admin'), 'under_review');
        Sanctum::actingAs($this->makeUser('superadmin'));

        $this->getJson('/api/admin/foundations/pending-count')->assertOk()->assertJsonPath('count', 1);
    }

    public function test_donor_categories_are_served_where_the_campaign_page_calls_them(): void
    {
        Sanctum::actingAs($this->makeUser('donor'));

        $this->getJson('/api/donor/categories')->assertOk();
    }

    /* ── Suspended users ── */

    public function test_suspending_a_user_signs_them_out_and_login_explains_why(): void
    {
        $donor = $this->makeUser('donor');
        $donor->createToken('auth_token');
        Sanctum::actingAs($this->makeUser('superadmin'));

        $this->patchJson("/api/admin/users/{$donor->id}/status", [
            'status' => 'suspended',
            'suspension_reason' => 'Fraudulent donation proofs',
        ])->assertOk();

        $this->assertSame(0, $donor->tokens()->count());

        $this->postJson('/api/login', ['email' => $donor->email, 'password' => 'Secret-pass-123!'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_suspended')
            ->assertJsonPath('reason', 'Fraudulent donation proofs');
    }
}
