<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pick-up schedule: the foundation sets the day (and morning / afternoon / exact time)
 * it will collect a Pick Up item donation; the donor is told and can confirm or ask
 * for another day; the foundation is told about the donor's answer.
 * Dates are wall-clock dates in the display time zone (Asia/Manila).
 */
class PickupScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $donor;
    private Foundation $foundation;
    private Campaign $campaign;
    private Donation $pickup;

    protected function setUp(): void
    {
        parent::setUp();
        // Fixed "now": Fri Oct 30 2026, 10:00 in Manila
        $this->travelTo(Carbon::parse('2026-10-30 10:00', 'Asia/Manila'));

        $this->admin = $this->makeUser('foundation_admin', 'Fiona', '+639180000001');
        $this->donor = $this->makeUser('donor', 'Dana', '+639170000003');
        $this->foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified']);
        $this->campaign = Campaign::create([
            'foundation_id' => $this->foundation->id, 'created_by' => $this->admin->id,
            'title' => 'School Supplies Drive', 'description' => '', 'type' => 'item',
            'goal_amount' => 0, 'current_amount' => 0, 'status' => 'active',
            'accepted_delivery_methods' => ['pickup', 'dropoff'],
        ]);
        $this->pickup = $this->itemDonation('pickup');
    }

    private function makeUser(string $role, string $first, string $phone): User
    {
        static $n = 0;
        $n++;
        return User::create([
            'first_name' => $first, 'last_name' => 'Tester', 'email' => "sched{$n}@example.com",
            'password' => 'Secret-pass-123!', 'role' => $role, 'phone' => $phone, 'status' => 'active',
        ]);
    }

    private function itemDonation(string $method, string $status = 'pending', ?User $donor = null): Donation
    {
        return Donation::create([
            'campaign_id' => $this->campaign->id, 'donor_id' => ($donor ?? $this->donor)->id, 'type' => 'item',
            'item_name' => 'Notebook', 'item_quantity' => 20, 'delivery_method' => $method,
            'delivery_address' => $method === 'pickup' ? 'Blue gate, Purok 3' : '1 Main St',
            'status' => $status, 'donated_at' => now(),
        ]);
    }

    private function schedule(Donation $d, array $data)
    {
        Sanctum::actingAs($this->admin);
        return $this->patchJson("/api/foundation/donations/{$d->id}/pickup-schedule", $data);
    }

    /* ── Foundation sets the schedule ── */

    public function test_the_foundation_schedules_a_pick_up_and_the_donor_is_told(): void
    {
        $this->schedule($this->pickup, ['date' => '2026-10-31', 'window' => 'morning', 'note' => 'Our driver Mark will call first.'])
            ->assertOk()
            ->assertJsonPath('pickup_schedule.label', 'Sat, Oct 31 · Morning (8 AM–12 NN)');

        $d = $this->pickup->fresh();
        $this->assertSame('2026-10-31', $d->pickup_date->toDateString());
        $this->assertSame('morning', $d->pickup_window);
        $this->assertSame('Our driver Mark will call first.', $d->pickup_schedule_note);

        $n = Notification::where('user_id', $this->donor->id)->sole();
        $this->assertSame('pickup_scheduled', $n->type);
        $this->assertSame('Pick-up scheduled', $n->title);
        $this->assertStringContainsString('Helping Hands will pick up your 20x Notebook on Sat, Oct 31 · Morning (8 AM–12 NN).', $n->message);
        $this->assertStringContainsString('Our driver Mark will call first.', $n->message);
        $this->assertSame('/donor/donations?highlight=' . $this->pickup->id . '&open=1', $n->donorLink());
    }

    public function test_an_exact_time_needs_a_time(): void
    {
        $this->schedule($this->pickup, ['date' => '2026-10-31', 'window' => 'exact'])->assertStatus(422);
        $this->schedule($this->pickup, ['date' => '2026-10-31', 'window' => 'exact', 'time' => '14:30'])
            ->assertOk()
            ->assertJsonPath('pickup_schedule.label', 'Sat, Oct 31 · 2:30 PM');
    }

    public function test_bad_schedules_are_refused(): void
    {
        $this->schedule($this->pickup, ['date' => '2026-10-29', 'window' => 'morning'])->assertStatus(422); // yesterday
        $this->schedule($this->pickup, ['date' => '2026-10-31', 'window' => 'evening'])->assertStatus(422);
        $this->schedule($this->pickup, ['date' => 'someday', 'window' => 'morning'])->assertStatus(422);
        $this->schedule($this->pickup, ['date' => '2027-06-01', 'window' => 'morning'])->assertStatus(422); // too far
        $this->schedule($this->pickup, ['date' => '2026-10-31', 'window' => 'morning', 'note' => str_repeat('a', 301)])->assertStatus(422);

        // today is fine
        $this->schedule($this->pickup, ['date' => '2026-10-30', 'window' => 'afternoon'])->assertOk();
    }

    public function test_only_pending_pick_ups_of_this_foundation_can_be_scheduled(): void
    {
        $this->schedule($this->itemDonation('dropoff'), ['date' => '2026-10-31', 'window' => 'morning'])->assertStatus(422);
        $this->schedule($this->itemDonation('pickup', 'received'), ['date' => '2026-10-31', 'window' => 'morning'])->assertStatus(422);

        $otherAdmin = $this->makeUser('foundation_admin', 'Olga', '+639180000009');
        Foundation::create(['user_id' => $otherAdmin->id, 'name' => 'Other', 'status' => 'verified']);
        Sanctum::actingAs($otherAdmin);
        $this->patchJson("/api/foundation/donations/{$this->pickup->id}/pickup-schedule", ['date' => '2026-10-31', 'window' => 'morning'])
            ->assertNotFound();

        Sanctum::actingAs($this->donor);
        $this->patchJson("/api/foundation/donations/{$this->pickup->id}/pickup-schedule", ['date' => '2026-10-31', 'window' => 'morning'])
            ->assertForbidden();
    }

    public function test_changing_the_schedule_says_so_and_resets_the_donor_answer(): void
    {
        // donor confirmed the first day → a new day needs a new confirmation
        $this->schedule($this->pickup, ['date' => '2026-10-31', 'window' => 'morning'])->assertOk();
        Sanctum::actingAs($this->donor);
        $this->postJson("/api/donor/donations/{$this->pickup->id}/pickup/confirm")->assertOk();

        $this->schedule($this->pickup, ['date' => '2026-11-02', 'window' => 'morning'])->assertOk();
        $this->assertNull($this->pickup->fresh()->pickup_confirmed_at);
        $this->assertSame('Pick-up schedule changed', Notification::where('user_id', $this->donor->id)->latest('id')->first()->title);

        // donor asked for another day → setting that day clears the request
        Sanctum::actingAs($this->donor);
        $this->postJson("/api/donor/donations/{$this->pickup->id}/pickup/reschedule", ['date' => '2026-11-03', 'window' => 'afternoon'])->assertOk();
        $this->schedule($this->pickup, ['date' => '2026-11-03', 'window' => 'afternoon'])->assertOk();
        $this->assertNull($this->pickup->fresh()->pickup_request_date);
    }

    /* ── Donor answers ── */

    public function test_the_donor_confirms_and_the_foundation_is_told(): void
    {
        $this->schedule($this->pickup, ['date' => '2026-10-31', 'window' => 'morning'])->assertOk();

        Sanctum::actingAs($this->donor);
        $this->postJson("/api/donor/donations/{$this->pickup->id}/pickup/confirm")
            ->assertOk()
            ->assertJsonPath('pickup_schedule.confirmed', true);

        $this->assertNotNull($this->pickup->fresh()->pickup_confirmed_at);
        $n = Notification::where('user_id', $this->admin->id)->latest('id')->first();
        $this->assertSame('pickup_confirmed', $n->type);
        $this->assertStringContainsString('Dana Tester confirmed the pick-up of 20x Notebook on Sat, Oct 31 · Morning (8 AM–12 NN).', $n->message);

        // shows in the donor's details too
        $this->getJson("/api/donor/donations/{$this->pickup->id}")
            ->assertJsonPath('pickup_schedule.confirmed', true)
            ->assertJsonPath('pickup_schedule.label', 'Sat, Oct 31 · Morning (8 AM–12 NN)');
    }

    public function test_the_donor_asks_for_another_day_and_the_foundation_is_told(): void
    {
        $this->schedule($this->pickup, ['date' => '2026-10-31', 'window' => 'morning'])->assertOk();
        Sanctum::actingAs($this->donor);
        $this->postJson("/api/donor/donations/{$this->pickup->id}/pickup/confirm")->assertOk();

        $this->postJson("/api/donor/donations/{$this->pickup->id}/pickup/reschedule", [
            'date' => '2026-11-02', 'window' => 'afternoon', 'note' => 'I work on Saturday.',
        ])->assertOk()
            ->assertJsonPath('pickup_schedule.confirmed', false)
            ->assertJsonPath('pickup_schedule.request.label', 'Mon, Nov 2 · Afternoon (1–5 PM)')
            ->assertJsonPath('pickup_schedule.request.note', 'I work on Saturday.');

        $n = Notification::where('user_id', $this->admin->id)->latest('id')->first();
        $this->assertSame('pickup_reschedule_requested', $n->type);
        $this->assertStringContainsString('Dana Tester asked for another pick-up day: Mon, Nov 2 · Afternoon (1–5 PM).', $n->message);
        $this->assertStringContainsString('I work on Saturday.', $n->message);
    }

    public function test_donor_answers_need_a_schedule_and_their_own_pending_donation(): void
    {
        Sanctum::actingAs($this->donor);
        $this->postJson("/api/donor/donations/{$this->pickup->id}/pickup/confirm")->assertStatus(422); // not scheduled yet

        $this->schedule($this->pickup, ['date' => '2026-10-31', 'window' => 'morning'])->assertOk();

        $stranger = $this->makeUser('donor', 'Sam', '+639170000077');
        Sanctum::actingAs($stranger);
        $this->postJson("/api/donor/donations/{$this->pickup->id}/pickup/confirm")->assertNotFound();

        Sanctum::actingAs($this->donor);
        $this->postJson("/api/donor/donations/{$this->pickup->id}/pickup/reschedule", ['date' => '2026-10-29', 'window' => 'morning'])
            ->assertStatus(422); // in the past

        $this->pickup->update(['status' => 'received']);
        $this->postJson("/api/donor/donations/{$this->pickup->id}/pickup/confirm")->assertStatus(422);
    }

    /* ── Contact for the driver ── */

    public function test_the_foundation_sees_the_donor_phone_only_for_pick_ups(): void
    {
        $this->itemDonation('dropoff');

        Sanctum::actingAs($this->admin);
        $rows = collect($this->getJson('/api/foundation/donations')->assertOk()->json());

        $this->assertSame('+639170000003', $rows->firstWhere('delivery_method', 'pickup')['donor']['phone']);
        $this->assertNull($rows->firstWhere('delivery_method', 'dropoff')['donor']['phone'] ?? null);
    }
}
