<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Item pick-up location:
 * - a donor choosing Pick Up may share their exact spot (GPS) besides the typed address;
 * - it is saved only for Pick Up, and only valid coordinates are accepted;
 * - the donor and that foundation's admin see it (for "Get directions"), other foundations don't;
 * - Donation Details also carries the foundation's address, for drop-off directions.
 */
class PickupLocationTest extends TestCase
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
        $this->foundation = Foundation::create([
            'user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified',
            'street' => '1 Main St', 'barangay' => 'Poblacion',
            'city_municipality' => 'Surigao City', 'province' => 'Surigao del Norte',
        ]);
        $this->campaign = Campaign::create([
            'foundation_id' => $this->foundation->id, 'created_by' => $this->admin->id,
            'title' => 'School Supplies Drive', 'description' => '', 'type' => 'item',
            'goal_amount' => 0, 'current_amount' => 0, 'status' => 'active',
            'accepted_delivery_methods' => ['pickup', 'dropoff'],
        ]);
    }

    private function makeUser(string $role): User
    {
        static $n = 0;
        $n++;
        return User::create([
            'first_name' => 'Test', 'last_name' => "User{$n}", 'email' => "pick{$n}@example.com",
            'password' => 'Secret-pass-123!', 'role' => $role,
            'phone' => '+63918200' . str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'status' => 'active',
        ]);
    }

    private function donateItem(array $extra = [])
    {
        Sanctum::actingAs($this->donor);
        return $this->postJson('/api/donor/donate', $extra + [
            'campaign_id' => $this->campaign->id, 'type' => 'item',
            'item_name' => 'Notebook', 'item_quantity' => 20,
            'delivery_method' => 'pickup', 'delivery_address' => 'Blue gate near the chapel, Purok 3',
        ]);
    }

    public function test_a_pick_up_donation_saves_the_shared_location(): void
    {
        $this->donateItem([
            'pickup_latitude' => 9.7843215, 'pickup_longitude' => 125.4890456, 'pickup_accuracy' => 12.6,
        ])->assertCreated();

        $d = Donation::sole();
        $this->assertEqualsWithDelta(9.7843215, $d->pickup_latitude, 0.0000001);
        $this->assertEqualsWithDelta(125.4890456, $d->pickup_longitude, 0.0000001);
        $this->assertSame(13, $d->pickup_accuracy); // whole metres
    }

    public function test_the_location_is_optional(): void
    {
        $this->donateItem()->assertCreated();

        $d = Donation::sole();
        $this->assertNull($d->pickup_latitude);
        $this->assertNull($d->pickup_longitude);
        $this->assertSame('Blue gate near the chapel, Purok 3', $d->delivery_address);
    }

    public function test_the_typed_address_is_still_required_with_a_location(): void
    {
        $this->donateItem(['delivery_address' => '', 'pickup_latitude' => 9.78, 'pickup_longitude' => 125.49])
            ->assertStatus(422);
        $this->assertSame(0, Donation::count());
    }

    public function test_invalid_or_half_coordinates_are_rejected(): void
    {
        $this->donateItem(['pickup_latitude' => 91, 'pickup_longitude' => 125.49])->assertStatus(422);
        $this->donateItem(['pickup_latitude' => 9.78, 'pickup_longitude' => 181])->assertStatus(422);
        $this->donateItem(['pickup_latitude' => 9.78])->assertStatus(422);
        $this->donateItem(['pickup_longitude' => 125.49])->assertStatus(422);
        $this->donateItem(['pickup_latitude' => 'here', 'pickup_longitude' => 125.49])->assertStatus(422);
        $this->assertSame(0, Donation::count());
    }

    public function test_a_drop_off_donation_never_keeps_a_location(): void
    {
        $this->donateItem([
            'delivery_method' => 'dropoff', 'delivery_address' => '',
            'pickup_latitude' => 9.78, 'pickup_longitude' => 125.49, 'pickup_accuracy' => 10,
        ])->assertCreated();

        $d = Donation::sole();
        $this->assertNull($d->pickup_latitude);
        $this->assertNull($d->pickup_longitude);
        $this->assertNull($d->pickup_accuracy);
    }

    public function test_the_donor_sees_the_location_and_the_foundation_address_in_details(): void
    {
        $this->donateItem(['pickup_latitude' => 9.7843215, 'pickup_longitude' => 125.4890456, 'pickup_accuracy' => 8])
            ->assertCreated();
        $id = Donation::sole()->id;

        $res = $this->getJson("/api/donor/donations/{$id}")->assertOk();
        $this->assertEqualsWithDelta(9.7843215, $res->json('pickup_latitude'), 0.0000001);
        $this->assertEqualsWithDelta(125.4890456, $res->json('pickup_longitude'), 0.0000001);
        $this->assertSame(8, $res->json('pickup_accuracy'));
        $this->assertSame('1 Main St, Poblacion, Surigao City, Surigao del Norte', $res->json('campaign.foundation_address'));

        // the list carries the same fields
        $this->assertEqualsWithDelta(9.7843215, $this->getJson('/api/donor/donations')->json('0.pickup_latitude'), 0.0000001);
    }

    public function test_the_foundation_admin_sees_the_location_for_directions(): void
    {
        $this->donateItem(['pickup_latitude' => 9.7843215, 'pickup_longitude' => 125.4890456, 'pickup_accuracy' => 8])
            ->assertCreated();

        Sanctum::actingAs($this->admin);
        $row = collect($this->getJson('/api/foundation/donations')->assertOk()->json())
            ->firstWhere('item_name', 'Notebook');

        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(9.7843215, $row['pickup_latitude'], 0.0000001);
        $this->assertEqualsWithDelta(125.4890456, $row['pickup_longitude'], 0.0000001);
        $this->assertSame(8, $row['pickup_accuracy']);
    }

    public function test_another_foundation_cannot_see_the_location(): void
    {
        $this->donateItem(['pickup_latitude' => 9.7843215, 'pickup_longitude' => 125.4890456])->assertCreated();

        $otherAdmin = $this->makeUser('foundation_admin');
        Foundation::create(['user_id' => $otherAdmin->id, 'name' => 'Other Foundation', 'status' => 'verified']);
        Sanctum::actingAs($otherAdmin);

        $body = $this->getJson('/api/foundation/donations')->assertOk()->getContent();
        $this->assertStringNotContainsString('9.7843215', $body);
        $this->assertStringNotContainsString('Notebook', $body);
    }
}
