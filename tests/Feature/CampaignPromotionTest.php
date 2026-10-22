<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignPromotion;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\Notification;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Promoting a running campaign: shown first to donors with a "Promoted" label
 * for 3 / 7 / 14 days, and followers get a notification. One at a time.
 */
class CampaignPromotionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Foundation $foundation;
    private Campaign $campaign;
    private Campaign $other;
    private User $follower;
    private User $liker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'foundation_admin']);
        $this->foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified']);

        $make = fn($title, $status = 'active') => Campaign::create([
            'foundation_id' => $this->foundation->id, 'created_by' => $this->admin->id,
            'title' => $title, 'description' => '', 'type' => 'monetary', 'status' => $status,
            'goal_amount' => 10000, 'current_amount' => 0,
        ]);
        $this->other = $make('Older Drive');
        $this->travel(1)->minutes();
        $this->campaign = $make('School Supplies Drive'); // newest

        $this->follower = User::factory()->create(['role' => 'donor']);
        $this->liker = User::factory()->create(['role' => 'donor']);
        DB::table('foundation_followers')->insert([
            ['donor_id' => $this->follower->id, 'foundation_id' => $this->foundation->id, 'type' => 'follow', 'created_at' => now(), 'updated_at' => now()],
            ['donor_id' => $this->liker->id, 'foundation_id' => $this->foundation->id, 'type' => 'like', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Sanctum::actingAs($this->admin);
    }

    private function promote(Campaign $c, array $data = [])
    {
        return $this->postJson("/api/foundation/campaigns/{$c->id}/promote", $data + [
            'message' => 'We need 30 more school kits!',
            'duration_days' => 7,
        ]);
    }

    /* ── Starting a promotion ── */

    public function test_promoting_a_running_campaign_lasts_the_chosen_days_and_notifies_followers(): void
    {
        $this->promote($this->campaign)->assertStatus(201)->assertJsonPath('promotion.duration_days', 7);

        $promo = CampaignPromotion::firstOrFail();
        $this->assertSame($this->campaign->id, $promo->campaign_id);
        $this->assertTrue($promo->ends_at->between(now()->addDays(7)->subMinute(), now()->addDays(7)->addMinute()));

        // Followers are told; people who only "liked" are not
        $note = Notification::where('user_id', $this->follower->id)->firstOrFail();
        $this->assertSame('campaign_promotion', $note->type);
        $this->assertStringContainsString('We need 30 more school kits!', $note->message);
        $this->assertSame(0, Notification::where('user_id', $this->liker->id)->count());
        $this->assertSame(1, $promo->notified_count);
    }

    public function test_only_3_7_or_14_days_and_a_message_are_accepted(): void
    {
        $this->promote($this->campaign, ['duration_days' => 5])->assertStatus(422);
        $this->promote($this->campaign, ['message' => ''])->assertStatus(422);
        $this->promote($this->campaign, ['duration_days' => 3])->assertStatus(201);
    }

    public function test_one_promotion_at_a_time_per_campaign(): void
    {
        $this->promote($this->campaign, ['duration_days' => 3])->assertStatus(201);
        $this->promote($this->campaign)->assertStatus(422);

        // A different campaign can still be promoted
        $this->promote($this->other)->assertStatus(201);

        // After it ends, the first campaign can be promoted again
        $this->travel(4)->days();
        $this->promote($this->campaign)->assertStatus(201);
        $this->assertSame(3, CampaignPromotion::count());
    }

    public function test_only_running_campaigns_can_be_promoted(): void
    {
        $this->campaign->update(['status' => 'paused']);
        $this->promote($this->campaign)->assertStatus(422);

        $this->campaign->update(['status' => 'completed']);
        $this->promote($this->campaign)->assertStatus(422);

        $this->assertSame(0, CampaignPromotion::count());
    }

    public function test_a_suspended_foundation_cannot_promote(): void
    {
        $this->foundation->update(['status' => 'suspended']);
        $this->promote($this->campaign)->assertStatus(403);
        $this->assertSame(0, Notification::count());
    }

    public function test_cannot_promote_another_foundations_campaign(): void
    {
        $stranger = User::factory()->create(['role' => 'foundation_admin']);
        Foundation::create(['user_id' => $stranger->id, 'name' => 'Other', 'status' => 'verified']);
        Sanctum::actingAs($stranger);

        $this->promote($this->campaign)->assertStatus(404);
    }

    /* ── What donors see ── */

    public function test_donors_see_a_promoted_campaign_first_with_its_message_until_it_ends(): void
    {
        $this->promote($this->other, ['message' => 'Almost there!']); // the OLDER campaign
        Sanctum::actingAs($this->follower);

        $list = $this->getJson('/api/donor/campaigns')->assertOk()->json();
        $this->assertSame('Older Drive', $list[0]['title'], 'Promoted campaign should be shown first');
        $this->assertSame('Almost there!', $list[0]['promotion']['message']);
        $this->assertNull($list[1]['promotion']);

        $featured = $this->getJson('/api/donor/dashboard')->assertOk()->json('featured_campaigns');
        $this->assertSame('Older Drive', $featured[0]['title']);

        // After 7 days it is a normal campaign again (newest first)
        $this->travel(8)->days();
        $list = $this->getJson('/api/donor/campaigns')->json();
        $this->assertSame('School Supplies Drive', $list[0]['title']);
        $this->assertNull($list[1]['promotion']);
    }

    public function test_the_promotion_notification_opens_the_campaign(): void
    {
        $this->promote($this->campaign);
        Sanctum::actingAs($this->follower);

        $this->getJson('/api/donor/notifications')->assertOk()
            ->assertJsonPath('0.link', "/donor/campaigns?tab=new&highlight={$this->campaign->id}");
    }

    /* ── Timeline on the Reports page ── */

    private function donation(string $when, float $amount, string $status = 'received', ?User $donor = null): void
    {
        $gcash = PaymentMethod::firstOrCreate(['slug' => 'gcash'], ['name' => 'GCash', 'is_active' => true]);
        Donation::create([
            'campaign_id' => $this->campaign->id, 'donor_id' => ($donor ?? $this->follower)->id,
            'type' => 'monetary', 'amount' => $amount, 'payment_method_id' => $gcash->id,
            'status' => $status, 'donated_at' => $when,
        ]);
    }

    public function test_timeline_by_day_shows_received_money_per_day_and_the_promotions(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(9, 0));
        $this->campaign->forceFill(['created_at' => now()])->save(); // created_at isn't mass-assignable
        $this->promote($this->campaign, ['duration_days' => 3]);
        $this->donation('2026-10-02 10:00:00', 500);
        $this->donation('2026-10-02 15:00:00', 250, 'received', $this->liker);
        $this->donation('2026-10-03 11:00:00', 999, 'pending');   // not received yet: not in the chart
        $this->travelTo(now()->setDate(2026, 10, 5)->setTime(12, 0));

        $res = $this->getJson("/api/foundation/campaigns/{$this->campaign->id}/timeline?group=day")->assertOk();

        $days = collect($res->json('buckets'))->keyBy('key');
        $this->assertSame(['2026-10-01', '2026-10-02', '2026-10-03', '2026-10-04', '2026-10-05'], $days->keys()->all());
        $this->assertEquals(750, $days['2026-10-02']['amount']);
        $this->assertSame(2, $days['2026-10-02']['donors']);
        $this->assertEquals(0, $days['2026-10-03']['amount']);

        $this->assertCount(1, $res->json('promotions'));
        $this->assertSame('We need 30 more school kits!', $res->json('promotions.0.message'));

        // Event list: newest first, includes who donated and the promotion
        $types = collect($res->json('events'))->pluck('type')->all();
        $this->assertSame(['donation', 'donation', 'donation', 'promotion', 'created'], $types);
        $this->assertStringContainsString($this->liker->first_name, $res->json('events.1.title'));
    }

    public function test_timeline_by_month_adds_up_each_month(): void
    {
        $this->travelTo(now()->setDate(2026, 8, 10));
        $this->campaign->forceFill(['created_at' => now()])->save(); // created_at isn't mass-assignable
        $this->donation('2026-08-15 10:00:00', 100);
        $this->donation('2026-09-01 10:00:00', 200);
        $this->donation('2026-09-20 10:00:00', 300);
        $this->travelTo(now()->setDate(2026, 10, 3));

        $res = $this->getJson("/api/foundation/campaigns/{$this->campaign->id}/timeline?group=month")->assertOk();

        $months = collect($res->json('buckets'))->keyBy('key');
        $this->assertSame(['2026-08', '2026-09', '2026-10'], $months->keys()->all());
        $this->assertEquals(100, $months['2026-08']['amount']);
        $this->assertEquals(500, $months['2026-09']['amount']);
        $this->assertEquals(0, $months['2026-10']['amount']);
    }

    public function test_timeline_days_follow_philippine_time_not_utc(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(1, 0));
        $this->campaign->forceFill(['created_at' => now()])->save();
        $this->promote($this->campaign, ['duration_days' => 3]);   // Oct 20 09:00 PH -> Oct 23 09:00 PH
        $this->donation('2026-10-21 23:30:00', 400);               // UTC = Oct 22, 7:30 AM in the Philippines
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(4, 0));

        $res = $this->getJson("/api/foundation/campaigns/{$this->campaign->id}/timeline?group=day")->assertOk();

        $days = collect($res->json('buckets'))->keyBy('key');
        $this->assertEquals(0, $days['2026-10-21']['amount']);
        $this->assertEquals(400, $days['2026-10-22']['amount'], 'A 7:30 AM donation belongs to that same day in the Philippines');
        $this->assertSame('2026-10-20', $res->json('promotions.0.start_key'));
        $this->assertSame('2026-10-23', $res->json('promotions.0.end_key'));
    }

    public function test_cannot_see_another_foundations_timeline(): void
    {
        $stranger = User::factory()->create(['role' => 'foundation_admin']);
        Foundation::create(['user_id' => $stranger->id, 'name' => 'Other', 'status' => 'verified']);
        Sanctum::actingAs($stranger);

        $this->getJson("/api/foundation/campaigns/{$this->campaign->id}/timeline")->assertStatus(404);
    }
}
