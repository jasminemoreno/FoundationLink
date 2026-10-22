<?php

namespace Tests\Feature;

use App\Models\Foundation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A foundation admin who stopped half-way through sign-up (closed the browser,
 * device turned off) can log in later and finish, instead of being stuck.
 */
class UnfinishedSignUpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'foundation_admin']);
        Sanctum::actingAs($this->admin); // a normal login, not the sign-up token
    }

    private function makeFoundation(string $status): Foundation
    {
        return Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => $status]);
    }

    /* ── The dashboard says what is missing ── */

    public function test_dashboard_says_to_add_a_foundation_when_there_is_none(): void
    {
        $this->getJson('/api/foundation/dashboard')
            ->assertOk()
            ->assertJsonPath('foundation', null)
            ->assertJsonPath('setup_step', 'create_foundation');
    }

    public function test_dashboard_says_to_upload_documents_when_they_were_never_sent(): void
    {
        $this->makeFoundation('pending_verification');

        $this->getJson('/api/foundation/dashboard')->assertOk()->assertJsonPath('setup_step', 'upload_documents');
    }

    public function test_dashboard_has_no_setup_step_once_documents_are_sent_or_approved(): void
    {
        $foundation = $this->makeFoundation('under_review');
        $this->getJson('/api/foundation/dashboard')->assertOk()->assertJsonPath('setup_step', null);

        $foundation->update(['status' => 'verified']);
        $this->getJson('/api/foundation/dashboard')->assertOk()->assertJsonPath('setup_step', null);
    }

    /* ── A logged-in admin can finish both missing steps ── */

    public function test_a_logged_in_admin_can_finish_sign_up_from_the_dashboard(): void
    {
        Storage::fake('public');

        $this->postJson('/api/foundations', ['name' => 'Helping Hands'])->assertStatus(201);
        $this->getJson('/api/foundation/dashboard')->assertJsonPath('setup_step', 'upload_documents');

        $this->post('/api/foundation-verification', [
            'admin_id_type' => 'passport',
            'doc_type' => 'sec_registration',
            'admin_files' => [UploadedFile::fake()->image('id.jpg')],
            'doc_files' => [UploadedFile::fake()->image('doc.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame('under_review', Foundation::where('user_id', $this->admin->id)->value('status'));
        $this->getJson('/api/foundation/dashboard')->assertJsonPath('setup_step', null);

        // Their normal login keeps working afterwards (only a sign-up token is retired)
        $this->getJson('/api/foundation/dashboard')->assertOk();
    }

    /* ── Unfinished sign-ups are not in the super admin's approval queue ── */

    public function test_foundations_without_documents_are_not_counted_as_waiting_for_approval(): void
    {
        $this->makeFoundation('pending_verification'); // never sent documents
        Foundation::create([
            'user_id' => User::factory()->create(['role' => 'foundation_admin'])->id,
            'name' => 'Sent Documents',
            'status' => 'under_review',
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'superadmin']));

        $this->getJson('/api/admin/foundations/pending-count')->assertOk()->assertJsonPath('count', 1);
    }
}
