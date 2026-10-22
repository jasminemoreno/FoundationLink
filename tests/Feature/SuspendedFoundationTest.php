<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignUpdate;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A foundation that isn't approved (e.g. suspended) can't change its campaigns
 * or post updates, even by calling the API directly. Approved ones work as before.
 */
class SuspendedFoundationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $donor;
    private Foundation $foundation;
    private Campaign $paused;
    private Campaign $completed;
    private CampaignUpdate $update;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'foundation_admin']);
        $this->donor = User::factory()->create(['role' => 'donor']);
        $this->foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified']);

        $make = fn($title, $status) => Campaign::create([
            'foundation_id' => $this->foundation->id, 'created_by' => $this->admin->id,
            'title' => $title, 'description' => '', 'type' => 'monetary', 'status' => $status,
        ]);
        $this->paused = $make('Paused Drive', 'paused');
        $this->completed = $make('Finished Drive', 'completed');
        $this->update = CampaignUpdate::create(['campaign_id' => $this->completed->id, 'title' => 'Thanks!']);

        // A past donor, who would be notified by a new update
        Donation::create([
            'campaign_id' => $this->completed->id, 'donor_id' => $this->donor->id,
            'type' => 'monetary', 'amount' => 100, 'status' => 'received',
        ]);

        Sanctum::actingAs($this->admin);
    }

    /** Every campaign / update action an admin can take. */
    private function actions(): array
    {
        $p = $this->paused->id;
        $c = $this->completed->id;
        $u = $this->update->id;

        return [
            'edit campaign' => fn() => $this->postJson("/api/foundation/campaigns/{$p}", ['title' => 'Changed']),
            'resume campaign' => fn() => $this->patchJson("/api/foundation/campaigns/{$p}/resume"),
            'complete campaign' => fn() => $this->patchJson("/api/foundation/campaigns/{$p}/complete"),
            'delete campaign' => fn() => $this->deleteJson("/api/foundation/campaigns/{$p}"),
            'post update' => fn() => $this->postJson("/api/foundation/campaigns/{$c}/updates", ['title' => 'News']),
            'edit update' => fn() => $this->postJson("/api/foundation/campaigns/{$c}/updates/{$u}", ['title' => 'Edited']),
            'delete update' => fn() => $this->deleteJson("/api/foundation/campaigns/{$c}/updates/{$u}"),
        ];
    }

    public function test_a_suspended_foundation_cannot_change_campaigns_or_post_updates(): void
    {
        $this->foundation->update(['status' => 'suspended']);

        foreach ($this->actions() as $name => $call) {
            $this->assertSame(403, $call()->status(), "Suspended foundation should not be able to: {$name}");
        }

        // Nothing changed, and no donor got a message
        $this->assertSame('paused', $this->paused->fresh()->status);
        $this->assertSame('Paused Drive', $this->paused->fresh()->title);
        $this->assertNotNull($this->paused->fresh());
        $this->assertSame('Thanks!', $this->update->fresh()->title);
        $this->assertSame(1, CampaignUpdate::count());
        $this->assertSame(0, Notification::where('user_id', $this->donor->id)->count());
    }

    public function test_pausing_is_also_blocked_for_a_suspended_foundation(): void
    {
        $active = Campaign::create([
            'foundation_id' => $this->foundation->id, 'created_by' => $this->admin->id,
            'title' => 'Active Drive', 'description' => '', 'type' => 'monetary', 'status' => 'active',
        ]);
        $this->foundation->update(['status' => 'suspended']);

        $this->patchJson("/api/foundation/campaigns/{$active->id}/pause", ['pause_reason' => 'Because'])->assertStatus(403);
        $this->assertSame('active', $active->fresh()->status);
    }

    public function test_an_approved_foundation_can_still_do_all_of_it(): void
    {
        foreach ($this->actions() as $name => $call) {
            $status = $call()->status();
            $this->assertContains($status, [200, 201], "Approved foundation should be able to: {$name} (got {$status})");

            // Put things back so the next action has something to act on
            $this->paused = Campaign::withTrashed()->find($this->paused->id);
            $this->paused->restore();
            $this->paused->update(['status' => 'paused']);
            if (!CampaignUpdate::find($this->update->id)) {
                $this->update = CampaignUpdate::create(['campaign_id' => $this->completed->id, 'title' => 'Thanks!']);
            }
        }

        // Posting an update still notifies past donors
        $this->assertGreaterThan(0, Notification::where('user_id', $this->donor->id)->count());
    }
}
