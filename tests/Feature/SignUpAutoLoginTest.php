<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * After signing up, people go straight in instead of to the login page:
 * donors get a login token right away; foundation admins get one when they
 * finish the last step (documents), and the temporary registration token is retired.
 */
class SignUpAutoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_donor_is_logged_in_right_away(): void
    {
        $res = $this->postJson('/api/register/donor', [
            'first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'ana@example.com',
            'phone' => '09171234567', 'password' => 'Secret-pass-123!', 'password_confirmation' => 'Secret-pass-123!',
            'gender' => 'female', 'birthdate' => '2000-01-15', 'address' => 'Surigao City',
        ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'role']]);

        $this->assertSame('donor', $res->json('user.role'));
        $this->withToken($res->json('token'))->getJson('/api/donor/profile')
            ->assertOk()->assertJsonPath('email', 'ana@example.com');
    }

    public function test_a_new_foundation_admin_is_logged_in_when_the_last_step_is_done(): void
    {
        Storage::fake('public');
        $register = $this->postJson('/api/foundation-admin/register', [
            'first_name' => 'New', 'last_name' => 'Admin', 'email' => 'new-admin@example.com',
            'phone' => '09171234568', 'password' => 'Secret-pass-123!', 'password_confirmation' => 'Secret-pass-123!',
        ])->assertOk();
        $registrationToken = $register->json('registration_token');
        $adminId = $register->json('user.id');

        $this->withToken($registrationToken)->postJson('/api/foundations', ['name' => 'Bright Futures'])->assertStatus(201);

        $done = $this->withToken($registrationToken)->post('/api/foundation-verification', [
            'admin_id_type' => 'passport',
            'doc_type' => 'sec_registration',
            'admin_files' => [UploadedFile::fake()->image('id.jpg')],
            'doc_files' => [UploadedFile::fake()->image('doc.jpg')],
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'role']]);

        // only the new login token is left; the registration token is gone
        $tokens = DB::table('personal_access_tokens')->where('tokenable_id', $adminId)->pluck('name')->all();
        $this->assertSame(['auth_token'], $tokens);

        app('auth')->forgetGuards();
        $this->withToken($done->json('token'))->getJson('/api/foundation/dashboard')
            ->assertOk()->assertJsonPath('foundation.status', 'under_review');
    }

    public function test_a_logged_in_admin_resubmitting_documents_keeps_their_session_and_gets_no_extra_token(): void
    {
        Storage::fake('public');
        $register = $this->postJson('/api/foundation-admin/register', [
            'first_name' => 'Re', 'last_name' => 'Submit', 'email' => 'resubmit@example.com',
            'phone' => '09171234569', 'password' => 'Secret-pass-123!', 'password_confirmation' => 'Secret-pass-123!',
        ])->assertOk();
        $adminId = $register->json('user.id');
        $this->withToken($register->json('registration_token'))->postJson('/api/foundations', ['name' => 'Again'])->assertStatus(201);
        DB::table('personal_access_tokens')->where('tokenable_id', $adminId)->delete();

        // logged in normally (a login token, not the registration one)
        $loginToken = \App\Models\User::find($adminId)->createToken('auth_token')->plainTextToken;
        app('auth')->forgetGuards();
        $res = $this->withToken($loginToken)->post('/api/foundation-verification', [
            'admin_id_type' => 'passport', 'doc_type' => 'sec_registration',
            'admin_files' => [UploadedFile::fake()->image('id.jpg')], 'doc_files' => [UploadedFile::fake()->image('doc.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertNull($res->json('token'));
        $this->assertSame(1, DB::table('personal_access_tokens')->where('tokenable_id', $adminId)->count());
    }
}
