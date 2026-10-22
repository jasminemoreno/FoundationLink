<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignPromotion;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Promotion types (urgent need / deadline / milestone) with ready-made messages
 * from the campaign's real numbers, and "days left" + "donated this week" on
 * the donor's promoted card.
 */
class PromotionTypesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 22)->setTime(2, 0)); // 10 AM in the Philippines

        $this->admin = User::factory()->create(['role' => 'foundation_admin']);
        $foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified']);
        $this->campaign = Campaign::create([
            'foundation_id' => $foundation->id, 'created_by' => $this->admin->id,
            'title' => 'School Supplies Drive', 'description' => '', 'type' => 'monetary', 'status' => 'active',
            'goal_amount' => 20000, 'current_amount' => 5250,
        ]);
        Sanctum::actingAs($this->admin);
    }

    private function suggestions()
    {
        return collect(
            $this->getJson("/api/foundation/campaigns/{$this->campaign->id}/promotion-suggestions")->assertOk()->json('types')
        )->keyBy('type');
    }

    /* ── Ready-made messages ── */

    public function test_urgent_and_milestone_messages_use_the_real_numbers(): void
    {
        $s = $this->suggestions();

        $this->assertTrue($s['urgent']['available']);
        $this->assertStringContainsString('₱14,750', $s['urgent']['message']);       // 20,000 - 5,250
        $this->assertStringContainsString('School Supplies Drive', $s['urgent']['message']);

        $this->assertTrue($s['milestone']['available']);
        $this->assertStringContainsString('26%', $s['milestone']['message']);        // 5,250 / 20,000
        $this->assertStringContainsString('₱20,000', $s['milestone']['message']);
    }

    public function test_deadline_is_only_offered_when_the_campaign_has_a_future_end_date(): void
    {
        $s = $this->suggestions();
        $this->assertFalse($s['deadline']['available']);
        $this->assertNotEmpty($s['deadline']['reason']);

        $this->campaign->update(['end_date' => '2026-10-27']); // 5 days away
        $s = $this->suggestions();
        $this->assertTrue($s['deadline']['available']);
        $this->assertStringContainsString('5 days left', $s['deadline']['message']);
    }

    public function test_ready_made_messages_always_fit_the_200_character_limit(): void
    {
        $this->campaign->update(['title' => str_repeat('Very Long Campaign Name ', 12), 'end_date' => '2026-11-30']);

        foreach ($this->suggestions() as $type => $s) {
            $this->assertLessThanOrEqual(200, mb_strlen($s['message']), "{$type} message is too long");
        }
    }

    /* ── Promoting with a type ── */

    public function test_promotion_saves_its_type_and_deadline_needs_an_end_date(): void
    {
        $send = fn($type) => $this->postJson("/api/foundation/campaigns/{$this->campaign->id}/promote", [
            'type' => $type, 'message' => 'Help us!', 'duration_days' => 3,
        ]);

        $send('flyers')->assertStatus(422);    // not a real type
        $send('deadline')->assertStatus(422);  // no end date yet

        $this->campaign->update(['end_date' => '2026-10-27']);
        $send('deadline')->assertStatus(201)->assertJsonPath('promotion.type', 'deadline');
        $this->assertSame('deadline', CampaignPromotion::firstOrFail()->type);
    }

    public function test_a_promotion_without_a_type_counts_as_urgent_need(): void
    {
        $this->postJson("/api/foundation/campaigns/{$this->campaign->id}/promote", [
            'message' => 'Help us!', 'duration_days' => 3,
        ])->assertStatus(201)->assertJsonPath('promotion.type', 'urgent');
    }

    /* ── Reports timeline shows the type ── */

    public function test_timeline_shows_each_promotions_type(): void
    {
        $this->postJson("/api/foundation/campaigns/{$this->campaign->id}/promote", [
            'type' => 'milestone', 'message' => "We're 26% there!", 'duration_days' => 7,
        ])->assertStatus(201);

        $res = $this->getJson("/api/foundation/campaigns/{$this->campaign->id}/timeline")->assertOk();
        $this->assertSame('milestone', $res->json('promotions.0.type'));
        $this->assertSame('Milestone', $res->json('promotions.0.type_label'));
        $promoEvent = collect($res->json('events'))->firstWhere('type', 'promotion');
        $this->assertStringContainsString('Milestone', $promoEvent['title']);
    }

    /* ── Donor's promoted card: days left + donated this week ── */

    public function test_promoted_card_shows_days_left_and_who_donated_this_week(): void
    {
        $this->campaign->update(['end_date' => '2026-10-27']);
        $this->postJson("/api/foundation/campaigns/{$this->campaign->id}/promote", [
            'type' => 'deadline', 'message' => 'Only 5 days left!', 'duration_days' => 7,
        ])->assertStatus(201);

        $give = function (User $donor, string $when, string $status = 'received') {
            Donation::create([
                'campaign_id' => $this->campaign->id, 'donor_id' => $donor->id, 'type' => 'monetary',
                'amount' => 100, 'status' => $status, 'donated_at' => $when,
            ]);
        };
        [$a, $b, $c, $d] = User::factory()->count(4)->create(['role' => 'donor'])->all();
        $give($a, '2026-10-21 10:00:00');
        $give($a, '2026-10-20 10:00:00');             // same donor twice: counted once
        $give($b, '2026-10-18 10:00:00', 'pending');  // pledged this week: counts
        $give($c, '2026-10-19 10:00:00', 'cancelled'); // cancelled: does not count
        $give($d, '2026-10-10 10:00:00');             // more than a week ago: does not count

        Sanctum::actingAs($a);
        $promo = $this->getJson('/api/donor/campaigns')->assertOk()->json('0.promotion');

        $this->assertSame('deadline', $promo['type']);
        $this->assertSame(5, $promo['days_left']);
        $this->assertSame(2, $promo['donated_this_week']);
    }
}
