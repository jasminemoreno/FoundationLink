<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Suspending a foundation needs a reason, which its admin is notified of and sees.
 * While suspended it can still see its donations, but not manage them.
 */
class FoundationSuspensionTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $donor;
    private Foundation $foundation;
    private Donation $donation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create(['role' => 'superadmin']);
        $this->admin = User::factory()->create(['role' => 'foundation_admin']);
        $this->donor = User::factory()->create(['role' => 'donor']);
        $this->foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified']);

        $campaign = Campaign::create([
            'foundation_id' => $this->foundation->id, 'created_by' => $this->admin->id,
            'title' => 'Food Drive', 'description' => '', 'type' => 'monetary', 'status' => 'active',
        ]);
        $this->donation = Donation::create([
            'campaign_id' => $campaign->id, 'donor_id' => $this->donor->id,
            'type' => 'monetary', 'amount' => 300, 'status' => 'pending', 'donated_at' => now(),
        ]);
    }

    private function suspend(array $body = ['suspension_reason' => 'Donors reported missing items'])
    {
        Sanctum::actingAs($this->superadmin);

        return $this->patchJson("/api/admin/foundations/{$this->foundation->id}/suspend", $body);
    }

    public function test_suspending_needs_a_reason(): void
    {
        $this->suspend([])->assertStatus(422)->assertJsonValidationErrors('suspension_reason');
        $this->suspend(['suspension_reason' => 'abc'])->assertStatus(422)->assertJsonValidationErrors('suspension_reason');

        $this->assertSame('verified', $this->foundation->fresh()->status);
        $this->assertSame(0, Notification::count());
    }

    public function test_the_admin_is_told_why_and_sees_the_reason(): void
    {
        $this->suspend()->assertOk();

        $fresh = $this->foundation->fresh();
        $this->assertSame('suspended', $fresh->status);
        $this->assertSame('Donors reported missing items', $fresh->suspension_reason);

        $note = Notification::where('user_id', $this->admin->id)->sole();
        $this->assertSame('foundation_suspended', $note->type);
        $this->assertStringContainsString('Donors reported missing items', $note->message);

        // The super admin's list and the foundation's own dashboard both carry the reason
        $list = collect($this->getJson('/api/admin/foundations')->assertOk()->json())->keyBy('id');
        $this->assertSame('Donors reported missing items', $list[$this->foundation->id]['suspension_reason']);

        Sanctum::actingAs($this->admin);
        $this->getJson('/api/foundation/dashboard')->assertOk()
            ->assertJsonPath('foundation.status', 'suspended')
            ->assertJsonPath('foundation.suspension_reason', 'Donors reported missing items');
    }

    public function test_unsuspending_clears_the_reason_and_tells_the_admin(): void
    {
        $this->suspend()->assertOk();
        $this->patchJson("/api/admin/foundations/{$this->foundation->id}/unsuspend")->assertOk();

        $fresh = $this->foundation->fresh();
        $this->assertSame('verified', $fresh->status);
        $this->assertNull($fresh->suspension_reason);

        $this->assertSame(
            ['foundation_suspended', 'foundation_unsuspended'],
            Notification::where('user_id', $this->admin->id)->orderBy('id')->pluck('type')->all()
        );
    }

    public function test_only_approved_foundations_can_be_suspended_and_only_suspended_ones_unsuspended(): void
    {
        $this->foundation->update(['status' => 'under_review']);

        $this->suspend()->assertStatus(422)->assertJson(['message' => 'Only approved foundations can be suspended.']);
        // Unsuspending must not approve a foundation nobody reviewed
        $this->patchJson("/api/admin/foundations/{$this->foundation->id}/unsuspend")
            ->assertStatus(422)->assertJson(['message' => 'This foundation is not suspended.']);

        $this->assertSame('under_review', $this->foundation->fresh()->status);
        $this->assertSame(0, Notification::count());
    }

    public function test_only_the_super_admin_can_suspend(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/admin/foundations/{$this->foundation->id}/suspend", ['suspension_reason' => 'Trying it myself'])
            ->assertForbidden();
        $this->assertSame('verified', $this->foundation->fresh()->status);
    }

    public function test_a_suspended_foundation_sees_its_donations_but_cannot_manage_them(): void
    {
        $this->suspend()->assertOk();
        Sanctum::actingAs($this->admin);

        $ids = collect($this->getJson('/api/foundation/donations')->assertOk()->json())->pluck('id');
        $this->assertTrue($ids->contains($this->donation->id), 'The donation should still be listed.');

        $this->patchJson("/api/foundation/donations/{$this->donation->id}/status", ['status' => 'received'])->assertForbidden();
        $this->assertSame('pending', $this->donation->fresh()->status);
    }

    public function test_a_foundation_still_under_review_sees_no_donations(): void
    {
        $this->foundation->update(['status' => 'under_review']);
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/foundation/donations')->assertOk()->assertExactJson([]);
    }
}
