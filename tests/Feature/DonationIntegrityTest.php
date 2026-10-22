<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignUpdate;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\Notification;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DonationIntegrityTest extends TestCase
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

        $this->donor = $this->makeUser('donor');
        $this->admin = $this->makeUser('foundation_admin');
        $this->foundation = Foundation::create([
            'user_id' => $this->admin->id,
            'name' => 'Helping Hands',
            'status' => 'verified',
        ]);
        // The payment_methods migration seeds GCash itself.
        $this->gcash = PaymentMethod::firstOrCreate(['slug' => 'gcash'], ['name' => 'GCash', 'is_active' => true]);
        $this->campaign = $this->makeCampaign($this->foundation, [
            'accepted_payment_methods' => [(string) $this->gcash->id],
        ]);
    }

    private function makeUser(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'first_name' => 'Test',
            'last_name' => "User{$n}",
            'email' => "user{$n}@example.com",
            'password' => 'Secret-pass-123!',
            'role' => $role,
            'phone' => '+63918000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'status' => 'active',
        ]);
    }

    private function makeCampaign(Foundation $foundation, array $overrides = []): Campaign
    {
        return Campaign::create($overrides + [
            'foundation_id' => $foundation->id,
            'created_by' => $foundation->user_id,
            'title' => 'School Supplies Drive',
            'description' => '',
            'type' => 'both',
            'goal_amount' => 10000,
            'current_amount' => 0,
            'status' => 'active',
        ]);
    }

    private function makeDonation(string $status, float $amount): Donation
    {
        return Donation::create([
            'campaign_id' => $this->campaign->id,
            'donor_id' => $this->donor->id,
            'type' => 'monetary',
            'amount' => $amount,
            'payment_method_id' => $this->gcash->id,
            'status' => $status,
            'donated_at' => now(),
        ]);
    }

    /* ── Campaign "raised" only counts donations the foundation confirmed ── */

    public function test_raised_amount_changes_only_when_a_donation_is_received_or_reversed(): void
    {
        Sanctum::actingAs($this->donor);
        $this->postJson('/api/donor/donate', [
            'campaign_id' => $this->campaign->id,
            'type' => 'monetary',
            'amount' => 500,
            'payment_method_id' => $this->gcash->id,
        ])->assertStatus(201);

        $this->assertEquals(0, $this->campaign->fresh()->current_amount, 'A pending donation must not count as raised.');

        $donationId = Donation::first()->id;
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/foundation/donations/{$donationId}/status", ['status' => 'received'])->assertOk();
        $this->assertEquals(500, $this->campaign->fresh()->current_amount);

        // Marking it received twice must not double-count.
        $this->patchJson("/api/foundation/donations/{$donationId}/status", ['status' => 'received'])->assertOk();
        $this->assertEquals(500, $this->campaign->fresh()->current_amount);

        $this->patchJson("/api/foundation/donations/{$donationId}/status", ['status' => 'cancelled'])->assertOk();
        $this->assertEquals(0, $this->campaign->fresh()->current_amount);
    }

    public function test_foundation_dashboard_and_reports_only_total_received_donations(): void
    {
        $this->makeDonation('pending', 100);
        $this->makeDonation('received', 200);
        $this->makeDonation('cancelled', 300);

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/foundation/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('total_monetary', 200)
            ->assertJsonPath('pending', 1);

        $report = $this->getJson('/api/foundation/reports')->assertOk();
        $this->assertEquals(200, $report->json('summary.total_raised'));
        $this->assertEquals(200, $report->json('per_campaign.0.raised'));
        $this->assertEquals(200, collect($report->json('monthly'))->sum('monetary'));
    }

    public function test_donor_dashboard_only_totals_received_donations(): void
    {
        $this->makeDonation('pending', 100);
        $this->makeDonation('received', 200);
        $this->makeDonation('cancelled', 300);

        Sanctum::actingAs($this->donor);

        $this->getJson('/api/donor/dashboard')->assertOk()->assertJsonPath('total_donated', 200);
    }

    /* ── Suspended / unverified foundations ── */

    public function test_donors_cannot_see_or_donate_to_campaigns_of_a_suspended_foundation(): void
    {
        $this->foundation->update(['status' => 'suspended']);
        Sanctum::actingAs($this->donor);

        $this->assertCount(0, $this->getJson('/api/donor/campaigns')->assertOk()->json());
        $this->assertCount(0, $this->getJson('/api/donor/dashboard')->assertOk()->json('featured_campaigns'));
        $this->getJson("/api/donor/campaigns/{$this->campaign->id}")->assertNotFound();

        $this->postJson('/api/donor/donate', [
            'campaign_id' => $this->campaign->id,
            'type' => 'monetary',
            'amount' => 500,
            'payment_method_id' => $this->gcash->id,
        ])->assertStatus(422);

        $this->assertSame(0, Donation::count());
    }

    public function test_donors_still_see_campaigns_of_a_verified_foundation(): void
    {
        Sanctum::actingAs($this->donor);

        $this->assertCount(1, $this->getJson('/api/donor/campaigns')->assertOk()->json());
        $this->getJson("/api/donor/campaigns/{$this->campaign->id}")->assertOk();
    }

    /* ── Super admin report: "all time" period ── */

    public function test_all_time_report_period_includes_every_received_donation(): void
    {
        $this->makeDonation('received', 1000);
        Sanctum::actingAs($this->makeUser('superadmin'));

        $this->getJson('/api/admin/reports?period=all')
            ->assertOk()
            ->assertJsonPath('kpis.0.change', '₱1,000 this period')
            ->assertJsonPath('top_donors.0.amount', '₱1,000');
    }

    /* ── Campaign-update notifications link to a page that exists ── */

    public function test_campaign_update_notification_links_to_the_foundation_page(): void
    {
        $update = CampaignUpdate::create(['campaign_id' => $this->campaign->id, 'title' => 'Thank you!']);
        Notification::create([
            'user_id' => $this->donor->id,
            'title' => 'Campaign Update',
            'message' => 'Thank you!',
            'type' => 'campaign_update',
            'notifiable_id' => $update->id,
            'notifiable_type' => CampaignUpdate::class,
        ]);

        Sanctum::actingAs($this->donor);

        $this->getJson('/api/donor/notifications')
            ->assertOk()
            ->assertJsonPath('0.link', "/donor/foundations/{$this->foundation->id}");
    }

    /* ── Donor password change ── */

    public function test_donor_password_change_requires_the_current_password(): void
    {
        Sanctum::actingAs($this->donor);

        $this->postJson('/api/donor/profile', [
            'password' => 'Brand-new-pass-1!',
            'password_confirmation' => 'Brand-new-pass-1!',
        ])->assertStatus(422);

        $this->postJson('/api/donor/profile', [
            'current_password' => 'wrong-password',
            'password' => 'Brand-new-pass-1!',
            'password_confirmation' => 'Brand-new-pass-1!',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('Secret-pass-123!', $this->donor->fresh()->password));

        $this->postJson('/api/donor/profile', [
            'current_password' => 'Secret-pass-123!',
            'password' => 'Brand-new-pass-1!',
            'password_confirmation' => 'Brand-new-pass-1!',
        ])->assertOk();

        $this->assertTrue(Hash::check('Brand-new-pass-1!', $this->donor->fresh()->password));
    }

    public function test_donor_password_change_enforces_the_sign_up_password_rules(): void
    {
        Sanctum::actingAs($this->donor);

        $this->postJson('/api/donor/profile', [
            'current_password' => 'Secret-pass-123!',
            'password' => 'short123',
            'password_confirmation' => 'short123',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('Secret-pass-123!', $this->donor->fresh()->password));
    }

    public function test_password_reset_enforces_the_sign_up_password_rules(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $this->donor->email,
            'token' => Hash::make('reset-token'),
            'created_at' => now(),
        ]);

        $this->postJson('/api/reset-password', [
            'email' => $this->donor->email,
            'reset_token' => 'reset-token',
            'password' => 'short123',
            'password_confirmation' => 'short123',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('Secret-pass-123!', $this->donor->fresh()->password));
    }

    /* ── Campaign status only changes through pause / resume / complete ── */

    public function test_editing_a_campaign_cannot_change_its_status(): void
    {
        $this->campaign->update(['status' => 'paused', 'pause_reason' => 'Restocking']);
        Sanctum::actingAs($this->admin);

        $this->postJson("/api/foundation/campaigns/{$this->campaign->id}", [
            'title' => 'School Supplies Drive',
            'status' => 'active',
        ])->assertOk();

        $this->assertSame('paused', $this->campaign->fresh()->status);
    }
}
