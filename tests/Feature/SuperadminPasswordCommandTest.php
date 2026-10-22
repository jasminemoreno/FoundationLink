<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperadminPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'superadmin', 'password' => 'Old-leaked-pass-1!']);
        $this->admin->createToken('auth_token');
    }

    public function test_sets_a_new_password_and_signs_out_old_sessions(): void
    {
        $this->artisan('superadmin:password')
            ->expectsQuestion('New password', 'Brand-new-secret-42!')
            ->expectsQuestion('Type it again', 'Brand-new-secret-42!')
            ->assertSuccessful();

        $fresh = $this->admin->fresh();
        $this->assertTrue(Hash::check('Brand-new-secret-42!', $fresh->password));
        $this->assertFalse(Hash::check('Old-leaked-pass-1!', $fresh->password));
        $this->assertSame(0, $fresh->tokens()->count());

        // The new password works for logging in
        $this->postJson('/api/login', ['email' => $fresh->email, 'password' => 'Brand-new-secret-42!'])->assertOk();
    }

    public function test_refuses_a_weak_password(): void
    {
        $this->artisan('superadmin:password')
            ->expectsQuestion('New password', 'short1!')
            ->expectsQuestion('Type it again', 'short1!')
            ->assertFailed();

        $this->assertTrue(Hash::check('Old-leaked-pass-1!', $this->admin->fresh()->password));
    }

    public function test_refuses_when_the_two_entries_differ(): void
    {
        $this->artisan('superadmin:password')
            ->expectsQuestion('New password', 'Brand-new-secret-42!')
            ->expectsQuestion('Type it again', 'Brand-new-secret-43!')
            ->assertFailed();

        $this->assertTrue(Hash::check('Old-leaked-pass-1!', $this->admin->fresh()->password));
    }
}
