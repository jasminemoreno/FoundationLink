<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A campaign stays "active" until the foundation marks it completed, but once its
 * end date is over it stops taking donations. The end date itself still counts.
 */
class CampaignEndDateTest extends TestCase
{
    use RefreshDatabase;

    private User $donor;
    private User $admin;
    private Foundation $foundation;
    private PaymentMethod $gcash;

    protected function setUp(): void
    {
        parent::setUp();

        // 10 Nov 2026, 9 AM in the Philippines
        $this->travelTo(Carbon::parse('2026-11-10 09:00', 'Asia/Manila'));

        $this->gcash = PaymentMethod::firstOrCreate(['slug' => 'gcash'], ['name' => 'GCash', 'is_active' => true]);
        $this->admin = $this->makeUser('foundation_admin');
        $this->donor = $this->makeUser('donor');
        $this->foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified']);
    }

    private function makeUser(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'first_name' => 'Test', 'last_name' => "User{$n}", 'email' => "end{$n}@example.com",
            'password' => 'Secret-pass-123!', 'role' => $role,
            'phone' => '+63917200' . str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'status' => 'active',
        ]);
    }

    private function makeCampaign(?string $endDate, string $title = 'Drive'): Campaign
    {
        return Campaign::create([
            'foundation_id' => $this->foundation->id, 'created_by' => $this->admin->id, 'title' => $title,
            'description' => '', 'type' => 'both', 'goal_amount' => 1000, 'current_amount' => 0,
            'start_date' => '2026-11-01', 'end_date' => $endDate, 'status' => 'active',
            'accepted_payment_methods' => [(string) $this->gcash->id],
            'accepted_delivery_methods' => ['pickup', 'dropoff'],
        ]);
    }

    private function donate(Campaign $campaign)
    {
        Sanctum::actingAs($this->donor);

        return $this->postJson('/api/donor/donate', [
            'campaign_id' => $campaign->id, 'type' => 'monetary', 'amount' => 500,
            'payment_method_id' => $this->gcash->id,
        ]);
    }

    public function test_a_campaign_past_its_end_date_refuses_donations(): void
    {
        $campaign = $this->makeCampaign('2026-11-09');

        $this->donate($campaign)
            ->assertStatus(422)
            ->assertJson(['message' => 'This campaign has ended and no longer accepts donations.']);

        $this->assertSame(0, Donation::count());
    }

    public function test_the_last_day_and_campaigns_without_an_end_date_still_take_donations(): void
    {
        $this->donate($this->makeCampaign('2026-11-10'))->assertStatus(201);
        $this->donate($this->makeCampaign(null))->assertStatus(201);

        $this->assertSame(2, Donation::count());
    }

    public function test_the_end_date_is_counted_in_philippine_time(): void
    {
        $campaign = $this->makeCampaign('2026-11-10');

        // 11:30 PM on the last day: still open
        $this->travelTo(Carbon::parse('2026-11-10 23:30', 'Asia/Manila'));
        $this->donate($campaign)->assertStatus(201);

        // 12:30 AM the next day (still 10 Nov in UTC): closed
        $this->travelTo(Carbon::parse('2026-11-11 00:30', 'Asia/Manila'));
        $this->donate($campaign)->assertStatus(422);

        $this->assertSame(1, Donation::count());
    }

    public function test_donor_pages_say_when_a_campaign_has_ended(): void
    {
        $ended = $this->makeCampaign('2026-11-09', 'Ended drive');
        $open = $this->makeCampaign('2026-11-30', 'Open drive');
        Sanctum::actingAs($this->donor);

        $list = collect($this->getJson('/api/donor/campaigns')->assertOk()->json())->keyBy('id');
        $this->assertTrue($list[$ended->id]['has_ended']);
        $this->assertFalse($list[$open->id]['has_ended']);

        $this->getJson("/api/donor/campaigns/{$ended->id}")->assertOk()->assertJson(['has_ended' => true]);

        $profile = collect($this->getJson("/api/donor/foundations/{$this->foundation->id}")->assertOk()->json('campaigns'))->keyBy('id');
        $this->assertTrue($profile[$ended->id]['has_ended']);
        $this->assertFalse($profile[$open->id]['has_ended']);
    }

    public function test_ended_campaigns_are_not_featured_on_the_home_page(): void
    {
        $this->makeCampaign('2026-11-09', 'Ended drive');
        $this->makeCampaign('2026-11-10', 'Last day drive');
        $this->makeCampaign(null, 'No end date drive');
        Sanctum::actingAs($this->donor);

        $titles = collect($this->getJson('/api/donor/dashboard')->assertOk()->json('featured_campaigns'))->pluck('title');

        $this->assertEqualsCanonicalizing(['Last day drive', 'No end date drive'], $titles->all());
    }

    public function test_an_ended_campaign_cannot_be_promoted(): void
    {
        $campaign = $this->makeCampaign('2026-11-09');
        Sanctum::actingAs($this->admin);

        $this->postJson("/api/foundation/campaigns/{$campaign->id}/promote", [
            'type' => 'urgent', 'message' => 'Please help us!', 'duration_days' => 7,
        ])->assertStatus(422)->assertJson(['message' => 'This campaign has ended, so it can no longer be promoted.']);

        $this->assertSame(0, $campaign->promotions()->count());
    }

    public function test_the_foundation_can_still_finish_an_ended_campaign(): void
    {
        $campaign = $this->makeCampaign('2026-11-09');
        $donation = Donation::create([
            'campaign_id' => $campaign->id, 'donor_id' => $this->donor->id, 'type' => 'monetary', 'amount' => 500,
            'status' => 'pending', 'donated_at' => now()->subDays(3),
        ]);
        Sanctum::actingAs($this->admin);

        // A donation made before the end date can still be confirmed
        $this->patchJson("/api/foundation/donations/{$donation->id}/status", ['status' => 'received'])->assertOk();
        $this->assertEquals(500, $campaign->fresh()->current_amount);

        $this->patchJson("/api/foundation/campaigns/{$campaign->id}/complete")->assertOk();
        $this->assertSame('completed', $campaign->fresh()->status);
    }
}
