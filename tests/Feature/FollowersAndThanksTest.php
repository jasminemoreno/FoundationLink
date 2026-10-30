<?php

namespace Tests\Feature;

use App\Models\Campaign;
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
 * Two-way engagement:
 * - foundation admins see WHO follows them (name + photo only, never email/phone),
 *   whether each follower has donated, and follower numbers for Reports;
 * - a thank-you note when marking a donation received, which the donor sees;
 * - a donor can open one of their own donations (Donation Details), not someone else's.
 */
class FollowersAndThanksTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Foundation $foundation;
    private Campaign $campaign;
    private PaymentMethod $gcash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeUser('foundation_admin', 'Fiona');
        $this->foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified', 'logo' => 'foundations/logos/hh.png']);
        $this->gcash = PaymentMethod::firstOrCreate(['slug' => 'gcash'], ['name' => 'GCash', 'is_active' => true]);
        $this->campaign = Campaign::create([
            'foundation_id' => $this->foundation->id, 'created_by' => $this->admin->id,
            'title' => 'School Supplies Drive', 'description' => '', 'type' => 'both',
            'goal_amount' => 10000, 'current_amount' => 0, 'status' => 'active',
            'accepted_payment_methods' => [(string) $this->gcash->id],
        ]);
    }

    private function makeUser(string $role, string $first = 'Test'): User
    {
        static $n = 0;
        $n++;
        return User::create([
            'first_name' => $first, 'last_name' => "User{$n}", 'email' => "eng{$n}@example.com",
            'password' => 'Secret-pass-123!', 'role' => $role,
            'phone' => '+63918100' . str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'status' => 'active',
        ]);
    }

    private function follow(User $donor, Foundation $foundation, string $when): void
    {
        DB::table('foundation_followers')->insert([
            'donor_id' => $donor->id, 'foundation_id' => $foundation->id, 'type' => 'follow',
            'created_at' => $when, 'updated_at' => $when,
        ]);
    }

    private function donate(User $donor, string $status, array $extra = []): Donation
    {
        return Donation::create($extra + [
            'campaign_id' => $this->campaign->id, 'donor_id' => $donor->id, 'type' => 'monetary',
            'amount' => 500, 'payment_method_id' => $this->gcash->id, 'status' => $status, 'donated_at' => now(),
        ]);
    }

    /* ── Followers ── */

    public function test_a_foundation_admin_sees_who_follows_them_without_contact_details(): void
    {
        $ana = $this->makeUser('donor', 'Ana');
        $ben = $this->makeUser('donor', 'Ben');
        $this->follow($ana, $this->foundation, now()->subMinute()->toDateTimeString()); // this month on any test date
        $this->follow($ben, $this->foundation, now()->subMonths(2)->toDateTimeString());
        $this->donate($ana, 'received');

        // someone following ANOTHER foundation, and a "like" (not a follow), are not listed
        $other = Foundation::create(['user_id' => $this->makeUser('foundation_admin')->id, 'name' => 'Other', 'status' => 'verified']);
        $this->follow($this->makeUser('donor', 'Cid'), $other, now()->toDateTimeString());
        DB::table('foundation_followers')->insert(['donor_id' => $ben->id, 'foundation_id' => $this->foundation->id, 'type' => 'like', 'created_at' => now(), 'updated_at' => now()]);

        Sanctum::actingAs($this->admin);
        $res = $this->getJson('/api/foundation/followers')->assertOk();

        $this->assertSame(['Ana', 'Ben'], collect($res->json('followers'))->pluck('name')->map(fn($n) => explode(' ', $n)[0])->all()); // newest first
        $first = $res->json('followers.0');
        $this->assertTrue($first['has_donated']);
        $this->assertFalse($res->json('followers.1.has_donated'));
        foreach (['email', 'phone', 'address', 'birthdate'] as $private) {
            $this->assertArrayNotHasKey($private, $first, "followers list must not include {$private}");
        }
        $res->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.donated', 1)
            ->assertJsonPath('summary.new_this_month', 1)
            ->assertJsonPath('summary.became_donors_percent', 50);
        $this->assertCount(6, $res->json('by_month')); // last 6 months, for the Reports chart
        $this->assertSame(1, collect($res->json('by_month'))->last()['count']);
    }

    public function test_donors_cannot_see_a_followers_list(): void
    {
        Sanctum::actingAs($this->makeUser('donor'));
        $this->getJson('/api/foundation/followers')->assertForbidden();
    }

    /* ── Thank-you note ── */

    public function test_marking_received_can_include_a_thank_you_note_the_donor_sees(): void
    {
        $dana = $this->makeUser('donor', 'Dana');
        $donation = $this->donate($dana, 'pending');

        Sanctum::actingAs($this->admin);
        $this->patchJson("/api/foundation/donations/{$donation->id}/status", [
            'status' => 'received',
            'thank_you_note' => 'Thank you, Dana! Your gift bought notebooks for 20 pupils.',
        ])->assertOk();

        $this->assertSame('Thank you, Dana! Your gift bought notebooks for 20 pupils.', $donation->fresh()->thank_you_note);
        $note = Notification::where('user_id', $dana->id)->where('type', 'donation_received')->sole();
        $this->assertStringContainsString('notebooks for 20 pupils', $note->message);

        Sanctum::actingAs($dana);
        $this->getJson('/api/donor/donations')->assertJsonPath('0.thank_you_note', 'Thank you, Dana! Your gift bought notebooks for 20 pupils.');
    }

    public function test_the_thank_you_note_is_optional_and_limited_in_length(): void
    {
        $donation = $this->donate($this->makeUser('donor'), 'pending');
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/foundation/donations/{$donation->id}/status", ['status' => 'received', 'thank_you_note' => str_repeat('a', 501)])->assertStatus(422);
        $this->patchJson("/api/foundation/donations/{$donation->id}/status", ['status' => 'received'])->assertOk();
        $this->assertNull($donation->fresh()->thank_you_note);
    }

    /* ── Donation Details ── */

    public function test_a_donor_can_open_their_own_donation_with_its_foundation(): void
    {
        $dana = $this->makeUser('donor', 'Dana');
        $donation = $this->donate($dana, 'received', ['notes' => 'For grade 1', 'thank_you_note' => 'Salamat!']);

        Sanctum::actingAs($dana);
        $this->getJson("/api/donor/donations/{$donation->id}")
            ->assertOk()
            ->assertJsonPath('id', $donation->id)
            ->assertJsonPath('reference', sprintf('FL-%06d', $donation->id))
            ->assertJsonPath('payment_method', 'GCash')
            ->assertJsonPath('thank_you_note', 'Salamat!')
            ->assertJsonPath('campaign.title', 'School Supplies Drive')
            ->assertJsonPath('campaign.foundation', 'Helping Hands')
            ->assertJsonPath('campaign.foundation_id', $this->foundation->id)
            ->assertJsonPath('campaign.foundation_logo', 'foundations/logos/hh.png');
    }

    public function test_a_donor_cannot_open_someone_elses_donation(): void
    {
        $donation = $this->donate($this->makeUser('donor', 'Dana'), 'pending');

        Sanctum::actingAs($this->makeUser('donor', 'Eve'));
        $this->getJson("/api/donor/donations/{$donation->id}")->assertNotFound();
    }
}
