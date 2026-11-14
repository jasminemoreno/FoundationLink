<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Both report pages can be limited to a "From / To" range of whole days in
 * Philippine time. No dates means all time.
 */
class ReportDateRangeTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $donor;
    private Foundation $foundation;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        // 20 Nov 2026, 10 AM in the Philippines
        $this->travelTo($this->at('2026-11-20 10:00'));

        $this->superadmin = User::factory()->create(['role' => 'superadmin']);
        $this->admin = User::factory()->create(['role' => 'foundation_admin']);
        $this->donor = User::factory()->create(['role' => 'donor', 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $this->foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified']);
        $this->campaign = Campaign::create([
            'foundation_id' => $this->foundation->id, 'created_by' => $this->admin->id,
            'title' => 'Food Drive', 'description' => '', 'type' => 'both', 'goal_amount' => 5000, 'status' => 'active',
        ]);
    }

    /** A Philippine local time, as stored (UTC) */
    private function at(string $local): Carbon
    {
        return Carbon::parse($local, 'Asia/Manila')->utc();
    }

    private function donate(string $local, float $amount, string $status = 'received', string $type = 'monetary'): Donation
    {
        return Donation::create([
            'campaign_id' => $this->campaign->id, 'donor_id' => $this->donor->id, 'type' => $type,
            'amount' => $type === 'monetary' ? $amount : null, 'item_name' => $type === 'item' ? 'Rice' : null,
            'status' => $status, 'donated_at' => $this->at($local),
        ]);
    }

    /** Oct 15 ₱1,000 · Nov 3 ₱500 · Nov 18 ₱200 (all received), plus a pending ₱9,999 and an item on Nov 4-5 */
    private function sampleDonations(): void
    {
        $this->donate('2026-10-15 09:00', 1000);
        $this->donate('2026-11-03 09:00', 500);
        $this->donate('2026-11-18 09:00', 200);
        $this->donate('2026-11-05 09:00', 9999, 'pending');
        $this->donate('2026-11-04 09:00', 0, 'received', 'item');
    }

    public function test_super_admin_report_counts_only_the_chosen_dates(): void
    {
        $this->sampleDonations();
        Sanctum::actingAs($this->superadmin);

        $this->getJson('/api/admin/reports?from=2026-11-01&to=2026-11-15')->assertOk()
            ->assertJsonPath('period', 'Nov 1, 2026 – Nov 15, 2026')
            ->assertJsonPath('kpis.0.value', '₱500')
            ->assertJsonPath('kpis.0.change', 'Nov 1, 2026 – Nov 15, 2026')
            ->assertJsonPath('kpis.3.label', 'Items Received')
            ->assertJsonPath('kpis.3.value', '1')
            ->assertJsonPath('top_donors.0.amount', '₱500')
            ->assertJsonPath('top_foundations.0.raised', '₱500')
            ->assertJsonCount(1, 'monthly')
            ->assertJsonPath('monthly.0.month', 'Nov')
            ->assertJsonPath('monthly.0.amount', 0.5);

        // No dates: everything received, ever
        $this->getJson('/api/admin/reports')->assertOk()
            ->assertJsonPath('kpis.0.value', '₱1,700')
            ->assertJsonPath('kpis.0.change', 'all time')
            ->assertJsonPath('top_donors.0.amount', '₱1,700');
    }

    public function test_the_dates_are_whole_days_in_philippine_time(): void
    {
        $this->donate('2026-11-01 00:10', 10);  // still Oct 31 in UTC: counted
        $this->donate('2026-11-15 23:30', 20);  // last minutes of the end date: counted
        $this->donate('2026-11-16 00:30', 40);  // still Nov 15 in UTC, but the next day here: not counted
        $this->donate('2026-10-31 23:50', 80);  // the day before: not counted
        Sanctum::actingAs($this->superadmin);

        $this->getJson('/api/admin/reports?from=2026-11-01&to=2026-11-15')->assertOk()
            ->assertJsonPath('kpis.0.value', '₱30');

        Sanctum::actingAs($this->admin);
        $this->getJson('/api/foundation/reports?from=2026-11-01&to=2026-11-15')->assertOk()
            ->assertJsonPath('summary.total_raised', 30);
    }

    public function test_foundation_report_follows_the_dates(): void
    {
        $this->sampleDonations();
        Sanctum::actingAs($this->admin);

        $res = $this->getJson('/api/foundation/reports?from=2026-11-01&to=2026-11-15')->assertOk()
            ->assertJsonPath('summary.total_raised', 500)
            ->assertJsonPath('summary.total_items', 1)
            ->assertJsonPath('summary.total_donations', 3) // ₱500, the pending ₱9,999 and the item
            ->assertJsonPath('summary.total_campaigns', 1)
            ->assertJsonCount(1, 'monthly')
            ->assertJsonPath('monthly.0.month', 'Nov 2026')
            ->assertJsonPath('monthly.0.monetary', 500)
            ->assertJsonPath('per_campaign.0.raised', 500);

        // No dates: all time, chart shows the last 6 months
        $this->getJson('/api/foundation/reports')->assertOk()
            ->assertJsonPath('summary.total_raised', 1700)
            ->assertJsonCount(6, 'monthly')
            ->assertJsonPath('monthly.5.month', 'Nov 2026')
            ->assertJsonPath('monthly.4.monetary', 1000);
    }

    public function test_followers_follow_the_dates(): void
    {
        $follow = fn(User $donor, string $local) => DB::table('foundation_followers')->insert([
            'donor_id' => $donor->id, 'foundation_id' => $this->foundation->id, 'type' => 'follow',
            'created_at' => $this->at($local), 'updated_at' => $this->at($local),
        ]);
        $follow($this->donor, '2026-10-10 12:00');
        $follow(User::factory()->create(['role' => 'donor']), '2026-11-05 12:00');
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/foundation/followers?from=2026-11-01&to=2026-11-15')->assertOk()
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.new_in_period', 1)
            ->assertJsonCount(1, 'by_month')
            ->assertJsonPath('by_month.0.label', 'Nov')
            ->assertJsonPath('by_month.0.count', 1);
    }

    public function test_the_chart_shows_the_chosen_months_up_to_two_years(): void
    {
        Sanctum::actingAs($this->superadmin);

        $this->getJson('/api/admin/reports?from=2026-01-01&to=2026-11-20')->assertOk()
            ->assertJsonCount(11, 'monthly')
            ->assertJsonPath('monthly.0.month', 'Jan')
            ->assertJsonPath('monthly.10.current', true);

        // Longer ranges show their latest 24 months, with the year in the label
        $this->getJson('/api/admin/reports?from=2023-01-01&to=2026-11-20')->assertOk()
            ->assertJsonCount(24, 'monthly')
            ->assertJsonPath('monthly.0.month', 'Dec 24')
            ->assertJsonPath('monthly.23.month', 'Nov 26');
    }

    public function test_impossible_dates_are_refused(): void
    {
        Sanctum::actingAs($this->superadmin);
        $this->getJson('/api/admin/reports?from=2026-11-15&to=2026-11-01')
            ->assertStatus(422)->assertJsonValidationErrors('to');
        $this->getJson('/api/admin/reports?from=15/11/2026')
            ->assertStatus(422)->assertJsonValidationErrors('from');

        Sanctum::actingAs($this->admin);
        $this->getJson('/api/foundation/reports?from=2026-11-15&to=2026-11-01')
            ->assertStatus(422)->assertJsonValidationErrors('to');
    }

    public function test_only_a_start_or_only_an_end_date_works(): void
    {
        $this->sampleDonations();
        Sanctum::actingAs($this->superadmin);

        $this->getJson('/api/admin/reports?from=2026-11-01')->assertOk()
            ->assertJsonPath('kpis.0.value', '₱700')
            ->assertJsonPath('kpis.0.change', 'since Nov 1, 2026');
        $this->getJson('/api/admin/reports?to=2026-10-31')->assertOk()
            ->assertJsonPath('kpis.0.value', '₱1,000')
            ->assertJsonPath('kpis.0.change', 'until Oct 31, 2026');
    }
}
