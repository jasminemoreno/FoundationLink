<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignUpdate;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\PaymentMethod;
use App\Models\UpdateReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RobustnessFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $donor;
    private User $admin;
    private Foundation $foundation;
    private Campaign $campaign;
    private PaymentMethod $gcash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->donor = User::factory()->create(['role' => 'donor']);
        $this->admin = User::factory()->create(['role' => 'foundation_admin']);
        $this->foundation = Foundation::create([
            'user_id' => $this->admin->id,
            'name' => 'Helping Hands',
            'status' => 'verified',
        ]);
        // The payment_methods migration seeds GCash itself.
        $this->gcash = PaymentMethod::firstOrCreate(['slug' => 'gcash'], ['name' => 'GCash', 'is_active' => true]);
        $this->campaign = Campaign::create([
            'foundation_id' => $this->foundation->id,
            'created_by' => $this->admin->id,
            'title' => 'School Supplies Drive',
            'description' => '',
            'type' => 'both',
            'goal_amount' => 10000,
            'current_amount' => 0,
            'status' => 'active',
            // Stored as strings, the way the campaign form saves them.
            'accepted_payment_methods' => [(string) $this->gcash->id],
        ]);
    }

    private function donate(int $paymentMethodId)
    {
        return $this->postJson('/api/donor/donate', [
            'campaign_id' => $this->campaign->id,
            'type' => 'monetary',
            'amount' => 100,
            'payment_method_id' => $paymentMethodId,
        ]);
    }

    /* ── The user factory matches the real users table ── */

    public function test_user_factory_creates_a_valid_user(): void
    {
        $user = User::factory()->create();

        $this->assertNotEmpty($user->first_name);
        $this->assertNotEmpty($user->last_name);
        $this->assertSame('donor', $user->fresh()->role);
    }

    /* ── Donations use a payment method the campaign accepts ── */

    public function test_donation_with_an_accepted_payment_method_goes_through(): void
    {
        Sanctum::actingAs($this->donor);
        $this->donate($this->gcash->id)->assertStatus(201);
    }

    public function test_donation_with_a_payment_method_the_campaign_does_not_accept_is_rejected(): void
    {
        $maya = PaymentMethod::create(['name' => 'Maya Test', 'slug' => 'maya-test', 'is_active' => true]);
        Sanctum::actingAs($this->donor);

        $this->donate($maya->id)->assertStatus(422);
        $this->assertSame(0, Donation::count());
    }

    public function test_donation_with_a_disabled_payment_method_is_rejected(): void
    {
        $this->gcash->update(['is_active' => false]);
        Sanctum::actingAs($this->donor);

        $this->donate($this->gcash->id)->assertStatus(422);
        $this->assertSame(0, Donation::count());
    }

    /* ── Reactions ── */

    public function test_donor_cannot_react_to_updates_of_a_suspended_foundation(): void
    {
        $update = CampaignUpdate::create(['campaign_id' => $this->campaign->id, 'title' => 'Thanks']);
        $this->foundation->update(['status' => 'suspended']);
        Sanctum::actingAs($this->donor);

        $this->postJson("/api/donor/campaigns/{$this->campaign->id}/updates/{$update->id}/react")->assertNotFound();
        $this->assertSame(0, UpdateReaction::count());
    }

    public function test_donor_can_react_to_updates_of_a_verified_foundation(): void
    {
        $update = CampaignUpdate::create(['campaign_id' => $this->campaign->id, 'title' => 'Thanks']);
        Sanctum::actingAs($this->donor);

        $this->postJson("/api/donor/campaigns/{$this->campaign->id}/updates/{$update->id}/react")
            ->assertOk()
            ->assertJsonPath('reacted', true);
    }

    /* ── Forgot password does not reveal which emails are registered ── */

    public function test_forgot_password_answers_the_same_for_unknown_and_known_emails(): void
    {
        NotificationFacade::fake();

        $unknown = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])->assertOk();
        $known = $this->postJson('/api/forgot-password', ['email' => $this->donor->email])->assertOk();

        $this->assertSame($known->json('message'), $unknown->json('message'));
        $this->assertFalse(DB::table('password_reset_tokens')->where('email', 'nobody@example.com')->exists());
        // A real code is still issued (and mailed) for the registered email.
        $this->assertTrue(DB::table('password_reset_tokens')->where('email', $this->donor->email)->exists());
        NotificationFacade::assertCount(1);
    }

    /* ── Rejecting a foundation needs a reason ── */

    public function test_rejecting_a_foundation_requires_a_reason(): void
    {
        $pending = Foundation::create([
            'user_id' => User::factory()->create(['role' => 'foundation_admin'])->id,
            'name' => 'Pending One',
            'status' => 'under_review',
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => 'superadmin']));

        $this->postJson("/api/admin/foundations/{$pending->id}/reject", [])->assertStatus(422);
        $this->assertSame('under_review', $pending->fresh()->status);

        $this->postJson("/api/admin/foundations/{$pending->id}/reject", ['reason' => 'Documents are unreadable'])->assertOk();
        $this->assertSame('rejected', $pending->fresh()->status);
    }

    /* ── Mail failures don't turn a saved action into an error ── */

    public function test_approving_a_foundation_succeeds_even_if_the_email_fails(): void
    {
        $pending = Foundation::create([
            'user_id' => User::factory()->create(['role' => 'foundation_admin'])->id,
            'name' => 'Pending One',
            'status' => 'under_review',
        ]);
        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('SMTP down'));
        Sanctum::actingAs(User::factory()->create(['role' => 'superadmin']));

        $this->postJson("/api/admin/foundations/{$pending->id}/approve")->assertOk();
        $this->assertSame('verified', $pending->fresh()->status);
    }

    /* ── Profile phone numbers follow the sign-up rules ── */

    public function test_profile_phone_is_normalized_and_must_be_valid(): void
    {
        Sanctum::actingAs($this->donor);

        $this->postJson('/api/donor/profile', ['phone' => '12345'])->assertStatus(422);

        $this->postJson('/api/donor/profile', ['phone' => '0917 765 4321'])->assertOk();
        $this->assertSame('+639177654321', $this->donor->fresh()->phone);
    }

    public function test_profile_phone_cannot_copy_another_accounts_number(): void
    {
        $other = User::factory()->create(['role' => 'donor', 'phone' => '+639170000001']);
        Sanctum::actingAs($this->donor);

        $this->postJson('/api/donor/profile', ['phone' => '09170000001'])->assertStatus(422);
        $this->assertNotSame($other->phone, $this->donor->fresh()->phone);

        Sanctum::actingAs($this->admin);
        User::factory()->create(['role' => 'foundation_admin', 'phone' => '+639170000002']);
        $this->postJson('/api/foundation/profile', ['phone' => '09170000002'])->assertStatus(422);
    }

    /* ── Deleted campaigns / users don't crash pages that list history ── */

    public function test_donor_history_and_feed_still_load_after_a_campaign_is_deleted(): void
    {
        Donation::create([
            'campaign_id' => $this->campaign->id,
            'donor_id' => $this->donor->id,
            'type' => 'monetary',
            'amount' => 100,
            'status' => 'received',
        ]);
        CampaignUpdate::create(['campaign_id' => $this->campaign->id, 'title' => 'Thanks']);
        $this->campaign->delete();

        Sanctum::actingAs($this->donor);
        $this->getJson('/api/donor/donations')->assertOk()->assertJsonPath('0.campaign.title', 'School Supplies Drive');
        $this->getJson('/api/donor/dashboard')->assertOk();
        $this->getJson('/api/donor/feed')->assertOk();
    }

    public function test_foundation_pages_still_load_after_a_donor_account_is_deleted(): void
    {
        Donation::create([
            'campaign_id' => $this->campaign->id,
            'donor_id' => $this->donor->id,
            'type' => 'monetary',
            'amount' => 100,
            'status' => 'received',
        ]);
        $update = CampaignUpdate::create(['campaign_id' => $this->campaign->id, 'title' => 'Thanks']);
        UpdateReaction::create(['donor_id' => $this->donor->id, 'campaign_update_id' => $update->id]);
        $this->donor->delete(); // soft delete

        Sanctum::actingAs($this->admin);
        $this->getJson('/api/foundation/donors')->assertOk();
        $this->getJson('/api/foundation/donations')->assertOk();
        $this->getJson("/api/foundation/campaigns/{$this->campaign->id}/updates/{$update->id}/reactors")->assertOk();
    }

    /* ── Draft campaigns stay private ── */

    public function test_draft_campaigns_are_not_shown_on_the_foundation_page(): void
    {
        Campaign::create([
            'foundation_id' => $this->foundation->id,
            'created_by' => $this->admin->id,
            'title' => 'Secret Draft',
            'description' => '',
            'type' => 'monetary',
            'status' => 'draft',
        ]);
        Sanctum::actingAs($this->donor);

        $titles = collect($this->getJson("/api/donor/foundations/{$this->foundation->id}")->assertOk()->json('campaigns'))->pluck('title');
        $this->assertContains('School Supplies Drive', $titles);
        $this->assertNotContains('Secret Draft', $titles);
    }

    /* ── Editing an update can remove all of its photos ── */

    public function test_removing_every_photo_from_an_update_sticks(): void
    {
        $this->campaign->update(['status' => 'completed']);
        $update = CampaignUpdate::create([
            'campaign_id' => $this->campaign->id,
            'title' => 'Thanks',
            'photos' => ['campaigns/updates/a.jpg', 'campaigns/updates/b.jpg'],
        ]);
        Sanctum::actingAs($this->admin);

        // What the edit form sends when the admin removed both photos.
        $this->postJson("/api/foundation/campaigns/{$this->campaign->id}/updates/{$update->id}", [
            'title' => 'Thanks',
            'existing_photos_sent' => 1,
        ])->assertOk();

        $this->assertSame([], $update->fresh()->photos);
    }

    public function test_an_update_cannot_point_at_photos_it_never_had(): void
    {
        $update = CampaignUpdate::create([
            'campaign_id' => $this->campaign->id,
            'title' => 'Thanks',
            'photos' => ['campaigns/updates/a.jpg'],
        ]);
        Sanctum::actingAs($this->admin);

        $this->postJson("/api/foundation/campaigns/{$this->campaign->id}/updates/{$update->id}", [
            'title' => 'Thanks',
            'existing_photos_sent' => 1,
            'existing_photos' => ['campaigns/updates/a.jpg', 'foundation/admin_ids/someone-elses-id.jpg'],
        ])->assertOk();

        $this->assertSame(['campaigns/updates/a.jpg'], $update->fresh()->photos);
    }
}
