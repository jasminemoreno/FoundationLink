<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Donor Details (foundation admin → Donors → View): besides the totals, the admin sees
 * the donor's latest donations to THIS foundation (newest first, at most 5), when they
 * first donated, how many are still pending, and whether they follow the foundation.
 * Donation Details gets the last-change time for its history.
 */
class DonorDetailsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $donor;
    private Foundation $foundation;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeUser('foundation_admin');
        $this->donor = $this->makeUser('donor');
        $this->foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified']);
        $this->campaign = $this->makeCampaign($this->foundation, 'School Supplies Drive');
    }

    private function makeUser(string $role): User
    {
        static $n = 0;
        $n++;
        return User::create([
            'first_name' => 'Test', 'last_name' => "User{$n}", 'email' => "dd{$n}@example.com",
            'password' => 'Secret-pass-123!', 'role' => $role,
            'phone' => '+63918300' . str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'status' => 'active',
        ]);
    }

    private function makeCampaign(Foundation $f, string $title): Campaign
    {
        return Campaign::create([
            'foundation_id' => $f->id, 'created_by' => $f->user_id, 'title' => $title, 'description' => '',
            'type' => 'both', 'goal_amount' => 10000, 'current_amount' => 0, 'status' => 'active',
        ]);
    }

    private function give(Campaign $c, string $status, string $when, array $extra = []): Donation
    {
        $d = Donation::create($extra + [
            'campaign_id' => $c->id, 'donor_id' => $this->donor->id, 'type' => 'monetary',
            'amount' => 100, 'status' => $status, 'donated_at' => $when,
        ]);
        $d->forceFill(['created_at' => $when, 'updated_at' => $when])->save();
        return $d;
    }

    private function donorRow(): array
    {
        Sanctum::actingAs($this->admin);
        return collect($this->getJson('/api/foundation/donors')->assertOk()->json())->firstWhere('id', $this->donor->id);
    }

    public function test_donor_details_show_recent_donations_to_this_foundation_newest_first(): void
    {
        foreach (range(1, 6) as $i) {
            $this->give($this->campaign, $i === 6 ? 'pending' : 'received', "2026-10-0{$i} 09:00:00", ['amount' => $i * 100]);
        }
        // a donation to another foundation must not show up here
        $other = Foundation::create(['user_id' => $this->makeUser('foundation_admin')->id, 'name' => 'Other', 'status' => 'verified']);
        $this->give($this->makeCampaign($other, 'Other Drive'), 'received', '2026-10-09 09:00:00');

        $row = $this->donorRow();

        $this->assertCount(5, $row['recent']);
        $this->assertSame([600, 500, 400, 300, 200], array_map(fn($r) => (int) $r['amount'], $row['recent']));
        $this->assertSame('School Supplies Drive', $row['recent'][0]['campaign']);
        $this->assertSame('pending', $row['recent'][0]['status']);
        $this->assertArrayHasKey('id', $row['recent'][0]);
        $this->assertSame(1, $row['pending_count']);
        $this->assertStringStartsWith('2026-10-01', $row['first_donated']);
        $this->assertNotContains('Other Drive', $row['campaigns']);
    }

    public function test_recent_donations_follow_the_donation_date_not_the_record_date(): void
    {
        // recorded in the opposite order of when they were donated
        $older = $this->give($this->campaign, 'received', '2026-10-20 09:00:00', ['amount' => 111]);
        $older->forceFill(['donated_at' => '2026-10-01 09:00:00'])->save();
        $newer = $this->give($this->campaign, 'pending', '2026-10-02 09:00:00', ['amount' => 222]);
        $newer->forceFill(['donated_at' => '2026-10-15 09:00:00'])->save();

        $row = $this->donorRow();
        $this->assertSame([222, 111], array_map(fn($r) => (int) $r['amount'], $row['recent']));
        $this->assertStringStartsWith('2026-10-01', $row['first_donated']);
    }

    public function test_donor_details_say_whether_the_donor_follows_the_foundation(): void
    {
        $this->give($this->campaign, 'received', '2026-10-01 09:00:00');
        $this->assertFalse($this->donorRow()['is_follower']);

        DB::table('foundation_followers')->insert([
            'donor_id' => $this->donor->id, 'foundation_id' => $this->foundation->id, 'type' => 'follow',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertTrue($this->donorRow()['is_follower']);
    }

    public function test_donation_details_include_the_last_change_time(): void
    {
        $d = $this->give($this->campaign, 'received', '2026-10-05 14:30:00');
        Sanctum::actingAs($this->admin);
        $row = collect($this->getJson('/api/foundation/donations')->assertOk()->json())->firstWhere('id', $d->id);
        $this->assertStringStartsWith('2026-10-05', $row['updated_at']);
    }
}
