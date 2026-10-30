<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Foundation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Date rules the new calendars follow, enforced on the server too:
 * - a birthdate is always in the past (sign-up already checked; the profile now does too);
 * - a campaign can't end before it starts (on create, and on edit against the saved start date).
 */
class DateRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $donor;
    private Foundation $foundation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-30 10:00', 'Asia/Manila'));

        $this->admin = $this->makeUser('foundation_admin', '+639180000101');
        $this->donor = $this->makeUser('donor', '+639170000102');
        $this->foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified']);
    }

    private function makeUser(string $role, string $phone): User
    {
        static $n = 0;
        $n++;
        return User::create([
            'first_name' => 'Test', 'last_name' => "User{$n}", 'email' => "dates{$n}@example.com",
            'password' => 'Secret-pass-123!', 'role' => $role, 'phone' => $phone, 'status' => 'active',
        ]);
    }

    /* ── Birthdate on the profile ── */

    public function test_a_profile_birthdate_must_be_in_the_past(): void
    {
        Sanctum::actingAs($this->donor);

        $this->postJson('/api/donor/profile', ['birthdate' => '2026-11-15'])->assertStatus(422);
        $this->postJson('/api/donor/profile', ['birthdate' => now()->toDateString()])->assertStatus(422);

        $this->postJson('/api/donor/profile', ['birthdate' => '2000-03-05'])->assertOk();
        $this->assertSame('2000-03-05', substr((string) $this->donor->fresh()->birthdate, 0, 10));

        // still optional
        $this->postJson('/api/donor/profile', ['birthdate' => null])->assertOk();
    }

    /* ── Campaign dates ── */

    private function create(array $dates)
    {
        Sanctum::actingAs($this->admin);
        return $this->postJson('/api/foundation/campaigns', $dates + [
            'title' => 'Notebooks for pupils', 'type' => 'item', 'accepted_delivery_methods' => ['dropoff'],
        ]);
    }

    public function test_a_new_campaign_cannot_end_before_it_starts(): void
    {
        $this->create(['start_date' => '2026-11-10', 'end_date' => '2026-11-01'])->assertStatus(422);
        $this->assertSame(0, Campaign::count());

        $this->create(['start_date' => '2026-11-10', 'end_date' => '2026-11-10'])->assertSuccessful(); // same day is fine
        $this->create(['end_date' => '2026-12-01'])->assertSuccessful();                                // no start date
        $this->assertSame(2, Campaign::count());
    }

    public function test_an_edited_campaign_cannot_end_before_its_start(): void
    {
        $campaign = Campaign::create([
            'foundation_id' => $this->foundation->id, 'created_by' => $this->admin->id,
            'title' => 'Feeding Program', 'description' => '', 'type' => 'item',
            'goal_amount' => 0, 'current_amount' => 0, 'status' => 'active',
            'start_date' => '2026-11-10', 'end_date' => '2026-12-31',
        ]);
        Sanctum::actingAs($this->admin);

        // only the end date sent: checked against the saved start date
        $this->postJson("/api/foundation/campaigns/{$campaign->id}", ['end_date' => '2026-11-01'])->assertStatus(422);
        // both sent
        $this->postJson("/api/foundation/campaigns/{$campaign->id}", ['start_date' => '2026-12-01', 'end_date' => '2026-11-20'])->assertStatus(422);
        $this->assertSame('2026-12-31', substr((string) $campaign->fresh()->end_date, 0, 10));

        $this->postJson("/api/foundation/campaigns/{$campaign->id}", ['start_date' => '2026-11-05', 'end_date' => '2026-11-20'])->assertOk();
        $this->assertSame('2026-11-20', substr((string) $campaign->fresh()->end_date, 0, 10));
    }
}
